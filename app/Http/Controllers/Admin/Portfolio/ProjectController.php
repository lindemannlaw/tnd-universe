<?php

namespace App\Http\Controllers\Admin\Portfolio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Portfolio\Project\StoreRequest;
use App\Http\Requests\Admin\Portfolio\Project\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectController extends Controller
{
    public function index(): View {
        $projects = Project::latest()->get();

        return view('admin.portfolio.projects.index', compact('projects'));
    }

    public function create(Request $request): View|JsonResponse|string {
        if ($request->ajax()) {
            return view('admin.portfolio.projects.create')->render();
        }

        abort(404);
    }

    public function store(StoreRequest $request): View|JsonResponse|string {
        $data = $request->validated();
        $data = $this->prepareDescriptionBlocksData($request, $data);

        try {
            DB::beginTransaction();

            $project = Project::create($data);

            $project->description = $project->processImagesInDescription($project->getAttributes()['description']);
            $project->save();

            if ($request->hasFile('hero_image')) {
                $project->addMediaFromRequest('hero_image')
                    ->toMediaCollection($project->mediaHero);
            }

            foreach ($request->input('new_files') ?? [] as $index => $fileData) {
                $file = $request->file("new_files.{$index}.file");
                $fileName = trim((string) data_get($fileData, 'name', ''));

                if (!$file || !$fileName) continue;

                $project->addMedia($file)
                    ->withCustomProperties([
                        'name' => $fileName,
                    ])
                    ->toMediaCollection($project->mediaFiles);
            }

            foreach ($request->input('gallery') ?? [] as $index => $data) {
                $image = data_get($request->file('gallery'), $index . '.image');

                if ($image) {
                    $project->addMedia($image)->toMediaCollection($project->mediaGallery)->update(['order_column' => $index]);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.project_store_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.project_store_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => __('admin.success_create_data'),
                ],
                'html' => $this->getViewProjects(),
            ]);
        }

        abort(404);
    }

    public function edit(Request $request, Project $project): View|JsonResponse|string {
        if ($request->ajax()) {
            // #region agent log
            if ($request->header('X-Modal-Refresh')) {
                $editColSpans = [];
                foreach (($project->getTranslation('description_blocks', 'en') ?? []) as $bi => $block) {
                    foreach (data_get($block, 'items', []) as $ii => $item) {
                        $editColSpans["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                    }
                }
                Log::info('[debug-fb4a59] edit() modal-refresh – project data served to client', [
                    'hypothesisId' => 'H2',
                    'project_id' => $project->id,
                    'col_spans' => $editColSpans,
                ]);
            }
            // #endregion

            $html = view('admin.portfolio.projects.edit', compact('project'))->render();

            // When called for a post-save modal refresh, return a JSON envelope
            if ($request->header('X-Modal-Refresh')) {
                return response()->json(['html' => $html]);
            }

            return $html;
        }

        abort(404);
    }

    public function update(UpdateRequest $request, Project $project): View|JsonResponse|RedirectResponse|string {
        $data = $request->validated();

        // #region agent log
        $rawBlocks = $request->input('description_blocks', []);
        $rawColSpans = [];
        foreach (data_get($rawBlocks, 'en', []) as $bi => $block) {
            foreach (data_get($block, 'items', []) as $ii => $item) {
                $rawColSpans["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                $rawColSpans["en[$bi][$ii].col_start"] = data_get($item, 'col_start', 'MISSING');
            }
        }
        Log::info('[debug-fb4a59] PATCH received - col values', ['project_id' => $project->id, 'col_spans' => $rawColSpans]);
        // #endregion

        $data = $this->prepareDescriptionBlocksData($request, $data);

        // #region agent log
        $prepColSpans = [];
        foreach (data_get($data, 'description_blocks.en', []) as $bi => $block) {
            foreach (data_get($block, 'items', []) as $ii => $item) {
                $prepColSpans["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                $prepColSpans["en[$bi][$ii].col_start"] = data_get($item, 'col_start', 'MISSING');
            }
        }
        Log::info('[debug-fb4a59] After prepareDescriptionBlocksData - col values', ['col_spans' => $prepColSpans]);
        // #endregion

        try {
            DB::beginTransaction();

            // #region agent log (split updateOrFail to check isDirty)
            $originalRaw = $project->getRawOriginal('description_blocks');
            $project->fill($data);
            $newRaw = $project->getAttributes()['description_blocks'] ?? null;
            $dirtyKeys = array_keys($project->getDirty());
            $dbBlocksBeforeSave = [];
            foreach (($project->getTranslation('description_blocks', 'en') ?? []) as $bi => $block) {
                foreach (data_get($block, 'items', []) as $ii => $item) {
                    $dbBlocksBeforeSave["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                }
            }
            Log::info('[debug-fb4a59] pre-save isDirty', [
                'hypothesisId' => 'H4',
                'description_blocks_dirty' => $project->isDirty('description_blocks'),
                'description_dirty'        => $project->isDirty('description'),
                'all_dirty_keys'           => $dirtyKeys,
                'col_spans_in_attributes'  => $dbBlocksBeforeSave,
                'original_raw_snippet'     => mb_substr((string)$originalRaw, 0, 300),
                'new_raw_snippet'          => mb_substr((string)$newRaw, 0, 300),
                'raw_identical'            => $originalRaw === $newRaw,
            ]);
            if (!$project->save()) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }
            // #endregion

                // #region agent log
                $dbColSpans = [];
                foreach (($project->getTranslation('description_blocks', 'en') ?? []) as $bi => $block) {
                    foreach (data_get($block, 'items', []) as $ii => $item) {
                        $dbColSpans["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                    }
                }
                Log::info('[debug-fb4a59] After save - DB col values', ['col_spans' => $dbColSpans]);
                // #endregion

            $project->description = $project->processImagesInDescription($project->getAttributes()['description']);
            $project->save();

                // #region agent log
                $freshProject = $project->fresh();
                $freshColSpans = [];
                foreach (($freshProject->getTranslation('description_blocks', 'en') ?? []) as $bi => $block) {
                    foreach (data_get($block, 'items', []) as $ii => $item) {
                        $freshColSpans["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                    }
                }
                Log::info('[debug-fb4a59] After final save (fresh from DB) - col values', ['col_spans' => $freshColSpans]);
                // #endregion

            if ($request->hasFile('hero_image')) {
                $project->clearMediaCollection($project->mediaHero);
                $project->addMediaFromRequest('hero_image')
                    ->toMediaCollection($project->mediaHero);
            }

            foreach ($request->input('current_files') ?? [] as $id => $fileData) {
                $fileName = trim((string) data_get($fileData, 'name', ''));

                if (!$id || !$fileName) continue;

                $file = Media::find($id);

                // Skip stale file IDs instead of failing whole project update.
                if (!$file || (int) $file->model_id !== (int) $project->id) {
                    continue;
                }

                $file->setCustomProperty('name', $fileName);
                $file->save();
            }

            foreach ($request->input('new_files') ?? [] as $index => $fileData) {
                $file = $request->file("new_files.{$index}.file");
                $fileName = trim((string) data_get($fileData, 'name', ''));

                if (!$file || !$fileName) continue;

                $project->addMedia($file)
                    ->withCustomProperties([
                        'name' => $fileName,
                    ])
                    ->toMediaCollection($project->mediaFiles);
            }

            $galleryCurrentMediaIds = collect($request->input('gallery', []))->pluck('media_id')->all();

            foreach ($request->input('gallery') ?? [] as $index => $data) {
                $media_id = data_get($data, key: 'media_id');
                $image = data_get($request->file('gallery'), $index . '.image');
                $media = Media::find($media_id);

                if ($media && $image) {
                    $media->delete();
                }

                if ($image) {
                    $media = $project->addMedia($image)->toMediaCollection($project->mediaGallery);

                    $galleryCurrentMediaIds[] = $media->id;
                }

                if ($media) {
                    $media->update(['order_column' => $index]);
                }
            }

            $galleryToDelete = $project->getMedia($project->mediaGallery)->whereNotIn('id', $galleryCurrentMediaIds);

            $galleryToDelete->each->delete();

            DB::commit();

            // #region agent log
            $committedProject = $project->fresh();
            $committedColSpans = [];
            foreach (($committedProject->getTranslation('description_blocks', 'en') ?? []) as $bi => $block) {
                foreach (data_get($block, 'items', []) as $ii => $item) {
                    $committedColSpans["en[$bi][$ii].col_span"] = data_get($item, 'col_span', 'MISSING');
                }
            }
            Log::info('[debug-fb4a59] AFTER DB::commit() – committed col values', [
                'hypothesisId' => 'H1',
                'project_id' => $committedProject->id,
                'col_spans' => $committedColSpans,
            ]);
            // #endregion

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.project_update_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.project_update_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => __('admin.success_update_data'),
                ],
                'html' => $this->getViewProjects(),
            ]);
        }

        // return redirect()
        //     ->back()
        //     ->with('success', __('admin.success_update_data'));

        abort(404);
    }

    public function delete(Request $request, Project $project) {
        try {
            DB::beginTransaction();

            $project->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.project_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.project_delete_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => __('admin.success_delete_data'),
                ],
                'html' => $this->getViewProjects(),
            ]);
        }

        abort(404);
    }

    public function deleteFile(Request $request, Media $media) {
        $project = Project::find($media->model_id);

        try {
            DB::beginTransaction();

            $media->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error(__('errors.file_delete_failed'), ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => __('errors.file_delete_failed'),
                    'error' => $exception->getMessage(),
                ], 500);
            }

            if(app()->environment('local')) dd($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => __('admin.success_delete_data'),
                ],
                'html' => view('admin.portfolio.projects.files', compact('project'))->render(),
            ]);
        }

        abort(404);
    }

    public function getViewProjects(): View|string {
        $projects = Project::latest()->get();

        return view('admin.portfolio.projects.list', compact('projects'))->render();
    }

    private function prepareDescriptionBlocksData(Request $request, array $data): array
    {
        $descriptionBlocks = [];
        $description = [];

        foreach (supported_languages_keys() as $locale) {
            $localeBlocks = data_get($data, "description_blocks.{$locale}", []);
            $preparedLocaleBlocks = [];
            $legacyDescriptionParts = [];

            foreach ($localeBlocks as $blockIndex => $block) {
                $type = data_get($block, 'type');

            if (!in_array($type, ['text', 'floating_gallery', 'text_column', 'text_column_row'], true)) {
                continue;
            }

                if ($type === 'text') {
                    $content = (string)data_get($block, 'content', '');

                    $preparedLocaleBlocks[] = [
                        'type' => 'text',
                        'content' => $content,
                    ];

                    if (filled(trim(strip_tags($content)))) {
                        $legacyDescriptionParts[] = $content;
                    }

                    continue;
                }

                if ($type === 'text_column_row') {
                    $allowedColors = ['emerald-950', 'emerald-900', 'emerald-800', 'primary', 'gold-bright'];
                    $preparedItems = [];

                    foreach ((data_get($block, 'items') ?: []) as $itemIndex => $item) {
                        $colStart = max(1, min(12, (int)data_get($item, 'col_start', 1)));
                        $colSpan  = max(1, min(12, (int)data_get($item, 'col_span', 12)));
                        if (($colStart + $colSpan - 1) > 12) {
                            $colSpan = 12 - $colStart + 1;
                        }

                        $imgFile = data_get($request->file('description_blocks'), "{$locale}.{$blockIndex}.items.{$itemIndex}.image_file");
                        $imgOld  = data_get($item, 'image');
                        $imgUrl  = is_string($imgOld) ? $imgOld : null;
                        if ($imgFile) {
                            $path   = $imgFile->store('projects/description-blocks', 'public');
                            $imgUrl = Storage::url($path);
                        }

                        $imgAlignment = data_get($item, 'image_alignment', 'top');
                        if (!in_array($imgAlignment, ['top', 'left', 'right'])) {
                            $imgAlignment = 'top';
                        }

                        $headlineColor = data_get($item, 'headline_color', 'primary');

                        $preparedItems[] = [
                            'col_start'       => $colStart,
                            'col_span'        => $colSpan,
                            'headline'        => data_get($item, 'headline') ?: null,
                            'headline_color'  => in_array($headlineColor, $allowedColors) ? $headlineColor : 'primary',
                            'headline_font'   => data_get($item, 'headline_font', 'pangea') === 'nicevar' ? 'nicevar' : 'pangea',
                            'headline_line'   => (bool)data_get($item, 'headline_line', false),
                            'content'         => (string)data_get($item, 'content', ''),
                            'content_line'    => (bool)data_get($item, 'content_line', false),
                            'link_text'       => data_get($item, 'link_text') ?: null,
                            'link_url'        => data_get($item, 'link_url') ?: null,
                            'image'           => $imgUrl,
                            'image_alignment' => $imgAlignment,
                            'image_col_span'  => filled(data_get($item, 'image_col_span')) ? max(1, min(12, (int)data_get($item, 'image_col_span'))) : null,
                            'text_col_span'   => filled(data_get($item, 'text_col_span')) ? max(1, min(12, (int)data_get($item, 'text_col_span'))) : null,
                        ];
                    }

                    if (empty($preparedItems)) {
                        continue;
                    }

                    $preparedLocaleBlocks[] = [
                        'type'           => 'text_column_row',
                        'padding_top'    => max(0, min(300, (int)data_get($block, 'padding_top', 0))),
                        'padding_bottom' => max(0, min(300, (int)data_get($block, 'padding_bottom', 0))),
                        'items'          => $preparedItems,
                    ];

                    continue;
                }

                $items = data_get($block, 'items', []);
                $preparedItems = [];

                foreach ($items as $itemIndex => $item) {
                    $file = data_get($request->file('description_blocks'), "{$locale}.{$blockIndex}.items.{$itemIndex}.image_file");
                    $oldImage = data_get($item, 'image');
                    $image = is_string($oldImage) ? $oldImage : null;

                    if ($file) {
                        $path = $file->store('projects/description-blocks', 'public');
                        $image = Storage::url($path);
                    }

                    if (!$image) {
                        continue;
                    }

                    $colStart = max(1, min(12, (int)data_get($item, 'col_start', 1)));
                    $colSpan = max(1, min(12, (int)data_get($item, 'col_span', 12)));

                    if (($colStart + $colSpan - 1) > 12) {
                        $colSpan = 12 - $colStart + 1;
                    }

                    $preparedItems[] = [
                        'headline' => data_get($item, 'headline'),
                        'subhead' => data_get($item, 'subhead'),
                        'col_start' => $colStart,
                        'col_span' => $colSpan,
                        'image' => $image,
                    ];
                }

                if (empty($preparedItems)) {
                    continue;
                }

                $preparedLocaleBlocks[] = [
                        'type'           => 'floating_gallery',
                        'items'          => $preparedItems,
                        'padding_top'    => max(0, min(300, (int)data_get($block, 'padding_top', 0))),
                        'padding_bottom' => max(0, min(300, (int)data_get($block, 'padding_bottom', 0))),
                    ];
            }

            if (empty($preparedLocaleBlocks)) {
                $preparedLocaleBlocks[] = [
                    'type' => 'text',
                    'content' => '',
                ];
            }

            $descriptionBlocks[$locale] = array_values($preparedLocaleBlocks);
            $description[$locale] = implode(PHP_EOL . PHP_EOL, $legacyDescriptionParts);
        }

        // ── Server-side structural sync ───────────────────────────────────────
        // EN (first language) is the source of truth for block structure and
        // layout. All other languages are aligned to match EN's blocks/types
        // while preserving their own text-only fields.
        $locales      = supported_languages_keys();
        $primaryLocale = $locales[0];                              // 'en'
        $otherLocales  = array_slice($locales, 1);                 // ['de', …]

        // Text-only fields per block/item type — preserved from target language
        $textFields = [
            'text'            => ['content'],
            'text_column_row' => ['headline', 'content', 'link_text', 'link_url'],
            'floating_gallery' => ['headline', 'subhead'],
        ];

        foreach ($otherLocales as $otherLocale) {
            $primaryBlocks = $descriptionBlocks[$primaryLocale] ?? [];
            $otherBlocks   = $descriptionBlocks[$otherLocale]   ?? [];
            $synced        = [];

            foreach ($primaryBlocks as $blockIndex => $primaryBlock) {
                $primaryType = $primaryBlock['type'] ?? 'text';
                $otherBlock  = $otherBlocks[$blockIndex] ?? null;
                $otherType   = $otherBlock['type'] ?? null;

                if ($primaryType === 'text') {
                    $synced[] = [
                        'type'    => 'text',
                        'content' => ($otherType === 'text')
                            ? ($otherBlock['content'] ?? '')
                            : ($primaryBlock['content'] ?? ''),
                    ];
                    continue;
                }

                // For structured blocks (text_column_row, floating_gallery)
                // copy all layout from primary; overlay text fields from other
                $otherItems   = ($otherType === $primaryType) ? ($otherBlock['items'] ?? []) : [];
                $syncedItems  = [];

                foreach ($primaryBlock['items'] ?? [] as $itemIndex => $primaryItem) {
                    $otherItem  = $otherItems[$itemIndex] ?? [];
                    $mergedItem = $primaryItem; // start with primary (all layout)

                    foreach ($textFields[$primaryType] ?? [] as $field) {
                        if (array_key_exists($field, $otherItem)) {
                            $mergedItem[$field] = $otherItem[$field];
                        }
                    }

                    $syncedItems[] = $mergedItem;
                }

                $synced[] = array_merge($primaryBlock, ['items' => $syncedItems]);
            }

            $descriptionBlocks[$otherLocale] = array_values($synced);
        }

        // Mirror image URLs: if a language has an image and a sibling does not,
        // copy it (images are layout, uploaded once in any language).
        foreach ($locales as $localeA) {
            foreach ($locales as $localeB) {
                if ($localeA === $localeB) continue;
                foreach ($descriptionBlocks[$localeA] ?? [] as $blockIndex => $blockA) {
                    $type = $blockA['type'] ?? null;
                    if (!in_array($type, ['text_column_row', 'floating_gallery'], true)) continue;
                    foreach ($blockA['items'] ?? [] as $itemIndex => $itemA) {
                        $urlA = $itemA['image'] ?? null;
                        $urlB = $descriptionBlocks[$localeB][$blockIndex]['items'][$itemIndex]['image'] ?? null;
                        if ($urlA && !$urlB) {
                            $descriptionBlocks[$localeB][$blockIndex]['items'][$itemIndex]['image'] = $urlA;
                        }
                    }
                }
            }
        }

        $data['description_blocks'] = $descriptionBlocks;
        $data['description'] = $description;

        return $data;
    }
}
