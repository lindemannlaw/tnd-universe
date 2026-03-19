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

            $rawDesc = $project->getAttributes()['description'] ?? null;
            if ($rawDesc) {
                $processed = $project->processImagesInDescription($rawDesc);
                $project->setTranslations('description', $processed);
                $project->save();
            }

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
            $html = view('admin.portfolio.projects.edit', compact('project'))->render();

            if ($request->header('X-Modal-Refresh')) {
                return response()->json(['html' => $html]);
            }

            return $html;
        }

        abort(404);
    }

    public function update(UpdateRequest $request, Project $project): View|JsonResponse|RedirectResponse|string {
        $data = $request->validated();
        $data = $this->prepareDescriptionBlocksData($request, $data);

        // ── Snapshot old EN text values for timestamp comparison ──
        $oldTexts    = $this->extractEnTextValues($project);
        $rawOldTexts = $this->extractRawEnTextValues($project);

        try {
            DB::beginTransaction();

            // Prepare the description_blocks JSON ourselves to guarantee persistence
            $descBlocksJson = json_encode(
                $data['description_blocks'] ?? [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $project->fill($data);

            $rawDesc = $project->getAttributes()['description'] ?? null;
            if ($rawDesc) {
                $processed = $project->processImagesInDescription($rawDesc);
                $project->setTranslations('description', $processed);
            }

            Log::info('[debug-fb4a59] pre-save', [
                'project_id' => $project->id,
                'description_blocks_dirty' => $project->isDirty('description_blocks'),
                'all_dirty_keys' => array_keys($project->getDirty()),
            ]);

            $project->saveOrFail();

            // ── Fallback: force-write description_blocks directly via DB ──
            // This bypasses Eloquent/Spatie isDirty detection entirely.
            // If Eloquent skipped the write (isDirty=false due to cast mismatch),
            // this guarantees the prepared data reaches the database.
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['description_blocks' => $descBlocksJson]);

            if ($request->hasFile('hero_image')) {
                $project->clearMediaCollection($project->mediaHero);
                $project->addMediaFromRequest('hero_image')
                    ->toMediaCollection($project->mediaHero);
            }

            foreach ($request->input('current_files') ?? [] as $id => $fileData) {
                $fileName = trim((string) data_get($fileData, 'name', ''));

                if (!$id || !$fileName) continue;

                $file = Media::find($id);

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

            // ── Update text_timestamps for changed EN fields ──
            $project->refresh();
            $newTexts = $this->extractEnTextValues($project);
            $this->updateTextTimestampsOnSave($project, $oldTexts, $newTexts, $rawOldTexts);

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

    public function clone(Request $request, Project $project) {
        try {
            DB::beginTransaction();

            $clone = $project->replicate();

            // Append "Clone" to titles
            foreach (supported_languages_keys() as $locale) {
                $title = $clone->getTranslation('title', $locale, false);
                if (filled($title)) {
                    $clone->setTranslation('title', $locale, $title . ' Clone');
                }
            }

            // Generate unique slug
            $baseSlug = $project->slug . '-clone';
            $slug = $baseSlug;
            $counter = 1;
            while (Project::where('slug', $slug)->exists()) {
                $counter++;
                $slug = $baseSlug . '-' . $counter;
            }
            $clone->slug = $slug;

            // Reset timestamps and set inactive
            $clone->active = false;
            $clone->text_timestamps = null;
            $clone->sort = Project::max('sort') + 1;

            $clone->save();

            // Copy media (hero, gallery, files, description)
            foreach (['hero', 'gallery', 'files', 'description'] as $collection) {
                foreach ($project->getMedia($collection) as $media) {
                    $media->copy($clone, $collection);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Project clone failed', ['exception' => $exception]);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Duplizierung fehlgeschlagen',
                    'error' => $exception->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', __('errors.general'));
        }

        if ($request->ajax()) {
            return response()->json([
                'toast' => [
                    'type' => 'success',
                    'message' => "Projekt \"{$clone->title}\" erstellt",
                ],
                'html' => $this->getViewProjects(),
            ]);
        }

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

            if (!in_array($type, ['text', 'floating_gallery', 'text_column', 'text_column_row', 'video', 'embed', 'numbers'], true)) {
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

                if ($type === 'floating_gallery') {
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

                    continue;
                }

                if ($type === 'video') {
                    $allowedColors = ['emerald-950', 'emerald-900', 'emerald-800', 'primary', 'gold-bright'];
                    $colStart = max(1, min(12, (int)data_get($block, 'col_start', 1)));
                    $colSpan  = max(1, min(12, (int)data_get($block, 'col_span', 12)));
                    if (($colStart + $colSpan - 1) > 12) {
                        $colSpan = 12 - $colStart + 1;
                    }

                    $videoSource = data_get($block, 'video_source', 'upload');
                    if (!in_array($videoSource, ['upload', 'url'])) {
                        $videoSource = 'upload';
                    }

                    // Handle video file upload
                    $videoFile = data_get($request->file('description_blocks'), "{$locale}.{$blockIndex}.video_file");
                    $videoOld  = data_get($block, 'video');
                    $videoPath = is_string($videoOld) ? $videoOld : null;
                    if ($videoFile) {
                        $path = $videoFile->store('projects/description-blocks', 'public');
                        $videoPath = Storage::url($path);
                    }

                    $headlineColor = data_get($block, 'headline_color', 'primary');

                    $preparedLocaleBlocks[] = [
                        'type'           => 'video',
                        'padding_top'    => max(0, min(300, (int)data_get($block, 'padding_top', 0))),
                        'padding_bottom' => max(0, min(300, (int)data_get($block, 'padding_bottom', 0))),
                        'col_start'      => $colStart,
                        'col_span'       => $colSpan,
                        'video_source'   => $videoSource,
                        'video'          => $videoPath,
                        'video_url'      => data_get($block, 'video_url') ?: null,
                        'headline'       => data_get($block, 'headline') ?: null,
                        'headline_color' => in_array($headlineColor, $allowedColors) ? $headlineColor : 'primary',
                        'headline_font'  => data_get($block, 'headline_font', 'pangea') === 'nicevar' ? 'nicevar' : 'pangea',
                        'headline_line'  => (bool)data_get($block, 'headline_line', false),
                        'content'        => (string)data_get($block, 'content', ''),
                        'content_line'   => (bool)data_get($block, 'content_line', false),
                    ];

                    continue;
                }

                if ($type === 'embed') {
                    $colStart = max(1, min(12, (int)data_get($block, 'col_start', 1)));
                    $colSpan  = max(1, min(12, (int)data_get($block, 'col_span', 12)));
                    if (($colStart + $colSpan - 1) > 12) {
                        $colSpan = 12 - $colStart + 1;
                    }

                    $embedUrl = data_get($block, 'embed_url');
                    if (!filled($embedUrl)) {
                        continue;
                    }

                    $preparedLocaleBlocks[] = [
                        'type'           => 'embed',
                        'padding_top'    => max(0, min(300, (int)data_get($block, 'padding_top', 0))),
                        'padding_bottom' => max(0, min(300, (int)data_get($block, 'padding_bottom', 0))),
                        'col_start'      => $colStart,
                        'col_span'       => $colSpan,
                        'embed_url'      => $embedUrl,
                        'embed_height'   => max(100, min(2000, (int)data_get($block, 'embed_height', 500))),
                    ];

                    continue;
                }

                if ($type === 'numbers') {
                    $allowedColors = ['emerald-950', 'emerald-900', 'emerald-800', 'primary', 'gold-bright'];
                    $preparedItems = [];

                    foreach ((data_get($block, 'items') ?: []) as $itemIndex => $item) {
                        $lineColor = data_get($item, 'line_color', 'emerald-900');

                        $preparedItems[] = [
                            'title'      => data_get($item, 'title') ?: null,
                            'line_color' => in_array($lineColor, $allowedColors) ? $lineColor : 'emerald-900',
                            'number'     => data_get($item, 'number') ?: null,
                            'subline'    => data_get($item, 'subline') ?: null,
                        ];
                    }

                    if (empty($preparedItems)) {
                        continue;
                    }

                    $preparedLocaleBlocks[] = [
                        'type'              => 'numbers',
                        'padding_top'       => max(0, min(300, (int)data_get($block, 'padding_top', 0))),
                        'padding_bottom'    => max(0, min(300, (int)data_get($block, 'padding_bottom', 0))),
                        'headline'          => data_get($block, 'headline') ?: null,
                        'headline_col_span' => max(1, min(12, (int)data_get($block, 'headline_col_span', 12))),
                        'headline_line'     => (bool)data_get($block, 'headline_line', false),
                        'items'             => $preparedItems,
                    ];

                    continue;
                }
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
            'text'             => ['content'],
            'text_column_row'  => ['headline', 'content', 'link_text', 'link_url'],
            'floating_gallery' => ['headline', 'subhead'],
            'video'            => ['headline', 'content'],
            'numbers'          => ['headline', 'title', 'number', 'subline'],
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

                // For blocks without items (video, embed): copy layout from primary,
                // overlay text fields from other at block level
                if (in_array($primaryType, ['video', 'embed'], true)) {
                    $merged = $primaryBlock;
                    if ($otherType === $primaryType) {
                        foreach ($textFields[$primaryType] ?? [] as $field) {
                            if (array_key_exists($field, $otherBlock)) {
                                $merged[$field] = $otherBlock[$field];
                            }
                        }
                    }
                    $synced[] = $merged;
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
                    // Mirror video file URLs at block level
                    if ($type === 'video') {
                        $urlA = $blockA['video'] ?? null;
                        $urlB = $descriptionBlocks[$localeB][$blockIndex]['video'] ?? null;
                        if ($urlA && !$urlB) {
                            $descriptionBlocks[$localeB][$blockIndex]['video'] = $urlA;
                        }
                        continue;
                    }
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

    // =========================================================================
    // Text Timestamps — Change Detection for Translation & SEO
    // =========================================================================

    /**
     * API endpoint: mark fields as translated or SEO-generated.
     */
    public function updateTextTimestamps(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:translation,seo',
            'keys' => 'required|array|min:1',
            'keys.*' => 'string|max:200',
        ]);

        $type      = $request->input('type');
        $keys      = $request->input('keys');
        $now       = now()->toIso8601String();
        $timestamps = $project->text_timestamps ?? [];
        $field      = $type === 'translation' ? 'de_translated_at' : 'seo_generated_at';

        foreach ($keys as $key) {
            $timestamps[$key] = array_merge($timestamps[$key] ?? [], [$field => $now]);
        }

        DB::table('projects')
            ->where('id', $project->id)
            ->update(['text_timestamps' => json_encode($timestamps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        return response()->json(['ok' => true, 'updated' => count($keys)]);
    }

    /**
     * Apply translations directly to DB (called from translation overlay "Übernehmen").
     * Accepts a map of form field names → translated values, writes them to the DE locale.
     */
    public function applyTranslations(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'translations'        => 'required|array|min:1',
            'translations.*.key'  => 'required|string|max:500',
            'translations.*.text' => 'nullable|string',
            'timestamp_keys'      => 'nullable|array',
            'timestamp_keys.*'    => 'string|max:200',
        ]);

        $translations  = $request->input('translations');
        $timestampKeys = $request->input('timestamp_keys', []);
        $targetLocale  = 'de';

        try {
            DB::beginTransaction();

            // Reload fresh data from DB
            $project->refresh();

            // Apply translations to each field
            foreach ($translations as $item) {
                $formKey = $item['key'];    // e.g. "title[en]" or "description_blocks[en][1][items][0][headline]"
                $text    = $item['text'] ?? '';

                // Convert EN form key to DE
                $deKey = str_replace('[en]', "[$targetLocale]", $formKey);

                // Parse the key to determine which field/attribute to update
                $this->applyTranslationToProject($project, $deKey, $text, $targetLocale);
            }

            $project->saveOrFail();

            // Update timestamps: mark these fields as translated
            if (!empty($timestampKeys)) {
                $now        = now()->toIso8601String();
                $timestamps = $project->text_timestamps ?? [];
                foreach ($timestampKeys as $key) {
                    $timestamps[$key] = array_merge($timestamps[$key] ?? [], ['de_translated_at' => $now]);
                }
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['text_timestamps' => json_encode($timestamps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }

            DB::commit();

            return response()->json(['ok' => true, 'applied' => count($translations)]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[applyTranslations] Failed', [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply a single translated value to the project model for the DE locale.
     */
    private function applyTranslationToProject(Project $project, string $deFormKey, string $value, string $locale): void
    {
        // Simple fields: "title[de]", "short_description[de]", etc.
        if (preg_match('/^(\w+)\[' . $locale . '\]$/', $deFormKey, $m)) {
            $field = $m[1];
            if (in_array($field, ['title', 'short_description', 'location', 'seo_title', 'seo_description', 'seo_keywords'])) {
                $project->setTranslation($field, $locale, $value);
                return;
            }
        }

        // Property details: "property_details[de][property_type]"
        if (preg_match('/^property_details\[' . $locale . '\]\[(\w+)\]$/', $deFormKey, $m)) {
            $subField = $m[1];
            $pd = $project->getTranslation('property_details', $locale) ?? [];
            $pd[$subField] = $value;
            $project->setTranslation('property_details', $locale, $pd);
            return;
        }

        // Description blocks: "description_blocks[de][1][content]" or "description_blocks[de][1][items][0][headline]"
        if (preg_match('/^description_blocks\[' . $locale . '\]\[(\d+)\](?:\[items\]\[(\d+)\])?\[(\w+)\]$/', $deFormKey, $m)) {
            $blockIdx = (int) $m[1];
            $itemIdx  = $m[2] !== '' ? (int) $m[2] : null;
            $field    = $m[3];

            $blocks = $project->getTranslation('description_blocks', $locale) ?? [];

            if ($itemIdx !== null) {
                // Item field
                $blocks[$blockIdx]['items'][$itemIdx][$field] = $value;
            } else {
                // Block-level field
                $blocks[$blockIdx][$field] = $value;
            }

            $project->setTranslation('description_blocks', $locale, $blocks);
            return;
        }
    }

    /**
     * Extract all EN text values from the project as a flat key→value map.
     * Keys match the format used in text_timestamps (e.g. "title", "description_blocks.1.items.0.content").
     */
    private function extractEnTextValues(Project $project): array
    {
        $texts = [];
        $locale = 'en';

        // Simple translatable fields
        foreach (['title', 'short_description', 'location', 'seo_title', 'seo_description', 'seo_keywords'] as $field) {
            $texts[$field] = $this->stripForCompare($project->getTranslation($field, $locale) ?? '');
        }

        // Property details
        $pd = $project->getTranslation('property_details', $locale);
        if (is_array($pd)) {
            foreach (['property_type', 'status', 'year_built'] as $subField) {
                $texts["property_details.$subField"] = $this->stripForCompare($pd[$subField] ?? '');
            }
        }

        // Description blocks
        $blocks = $project->getTranslation('description_blocks', $locale);
        if (is_array($blocks)) {
            foreach ($blocks as $bi => $block) {
                $type = $block['type'] ?? '';

                if ($type === 'text') {
                    $texts["description_blocks.$bi.content"] = $this->stripForCompare($block['content'] ?? '');
                }

                if (in_array($type, ['text_column_row', 'floating_gallery'], true)) {
                    foreach ($block['items'] ?? [] as $ii => $item) {
                        foreach (['content', 'headline', 'link_text', 'link_url', 'subhead'] as $tf) {
                            if (isset($item[$tf]) || $type === 'text_column_row') {
                                $texts["description_blocks.$bi.items.$ii.$tf"] = $this->stripForCompare($item[$tf] ?? '');
                            }
                        }
                    }
                }

                if ($type === 'numbers') {
                    if (isset($block['headline'])) {
                        $texts["description_blocks.$bi.headline"] = $this->stripForCompare($block['headline'] ?? '');
                    }
                    foreach ($block['items'] ?? [] as $ii => $item) {
                        foreach (['title', 'number', 'subline'] as $tf) {
                            if (isset($item[$tf])) {
                                $texts["description_blocks.$bi.items.$ii.$tf"] = $this->stripForCompare($item[$tf] ?? '');
                            }
                        }
                    }
                }
            }
        }

        return $texts;
    }

    /**
     * Compare old vs new text values and update en_changed_at timestamps.
     */
    private function updateTextTimestampsOnSave(Project $project, array $oldTexts, array $newTexts, array $rawOldTexts = []): void
    {
        $now        = now()->toIso8601String();
        $timestamps = $project->text_timestamps ?? [];
        $changed    = false;

        // Check all new text values against old
        foreach ($newTexts as $key => $newValue) {
            $oldValue = $oldTexts[$key] ?? '';
            if ($newValue !== $oldValue) {
                // Store the RAW old text (before stripping) for JS diff display
                $rawOld = $rawOldTexts[$key] ?? $oldValue;
                $timestamps[$key] = array_merge($timestamps[$key] ?? [], [
                    'en_changed_at' => $now,
                    'en_old_text'   => mb_substr($rawOld, 0, 5000),
                ]);
                $changed = true;
            }
        }

        if ($changed) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['text_timestamps' => json_encode($timestamps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }

    /**
     * Extract raw (un-stripped) EN text values for storing as en_old_text.
     */
    private function extractRawEnTextValues(Project $project): array
    {
        $texts = [];
        $locale = 'en';

        foreach (['title', 'short_description', 'location', 'seo_title', 'seo_description', 'seo_keywords'] as $field) {
            $texts[$field] = (string) ($project->getTranslation($field, $locale) ?? '');
        }

        $pd = $project->getTranslation('property_details', $locale);
        if (is_array($pd)) {
            foreach (['property_type', 'status', 'year_built'] as $subField) {
                $texts["property_details.$subField"] = (string) ($pd[$subField] ?? '');
            }
        }

        $blocks = $project->getTranslation('description_blocks', $locale);
        if (is_array($blocks)) {
            foreach ($blocks as $bi => $block) {
                $type = $block['type'] ?? '';
                if ($type === 'text') {
                    $texts["description_blocks.$bi.content"] = (string) ($block['content'] ?? '');
                }
                if (in_array($type, ['text_column_row', 'floating_gallery'], true)) {
                    foreach ($block['items'] ?? [] as $ii => $item) {
                        foreach (['content', 'headline', 'link_text', 'link_url', 'subhead'] as $tf) {
                            if (isset($item[$tf]) || $type === 'text_column_row') {
                                $texts["description_blocks.$bi.items.$ii.$tf"] = (string) ($item[$tf] ?? '');
                            }
                        }
                    }
                }
                if ($type === 'numbers') {
                    if (isset($block['headline'])) {
                        $texts["description_blocks.$bi.headline"] = (string) ($block['headline'] ?? '');
                    }
                    foreach ($block['items'] ?? [] as $ii => $item) {
                        foreach (['title', 'number', 'subline'] as $tf) {
                            if (isset($item[$tf])) {
                                $texts["description_blocks.$bi.items.$ii.$tf"] = (string) ($item[$tf] ?? '');
                            }
                        }
                    }
                }
            }
        }

        return $texts;
    }

    private function stripForCompare(string $value): string
    {
        // Replace HTML tags with space (matching JS behavior), then normalize whitespace
        return trim(preg_replace('/\s+/', ' ', preg_replace('/<[^>]*>/', ' ', $value)));
    }
}
