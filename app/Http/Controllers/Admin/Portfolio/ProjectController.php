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
            return view('admin.portfolio.projects.edit', compact('project'))->render();
        }

        // return view('admin.portfolio.projects.show-edit', compact('project'));

        abort(404);
    }

    public function update(UpdateRequest $request, Project $project): View|JsonResponse|RedirectResponse|string {
        $data = $request->validated();
        $data = $this->prepareDescriptionBlocksData($request, $data);

        try {
            DB::beginTransaction();

            $project->updateOrFail($data);

            $project->description = $project->processImagesInDescription($project->getAttributes()['description']);
            $project->save();

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

                if (!in_array($type, ['text', 'floating_gallery', 'text_column'], true)) {
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

                if ($type === 'text_column') {
                    $colStart = max(1, min(12, (int)data_get($block, 'col_start', 1)));
                    $colSpan  = max(1, min(12, (int)data_get($block, 'col_span', 12)));
                    if (($colStart + $colSpan - 1) > 12) {
                        $colSpan = 12 - $colStart + 1;
                    }

                    $allowedColors = ['emerald-950', 'emerald-900', 'emerald-800', 'primary', 'gold-bright'];
                    $headlineColor = data_get($block, 'headline_color', 'primary');

                    $imgFile = data_get($request->file('description_blocks'), "{$locale}.{$blockIndex}.image_file");
                    $imgOld  = data_get($block, 'image');
                    $imgUrl  = is_string($imgOld) ? $imgOld : null;
                    if ($imgFile) {
                        $path   = $imgFile->store('projects/description-blocks', 'public');
                        $imgUrl = Storage::url($path);
                    }

                    $imgAlignment = data_get($block, 'image_alignment', 'top');
                    if (!in_array($imgAlignment, ['top', 'left', 'right'])) {
                        $imgAlignment = 'top';
                    }

                    $preparedLocaleBlocks[] = [
                        'type'            => 'text_column',
                        'headline'        => data_get($block, 'headline') ?: null,
                        'headline_color'  => in_array($headlineColor, $allowedColors) ? $headlineColor : 'primary',
                        'headline_font'   => data_get($block, 'headline_font', 'pangea') === 'nicevar' ? 'nicevar' : 'pangea',
                        'headline_line'   => (bool)data_get($block, 'headline_line', false),
                        'content'         => (string)data_get($block, 'content', ''),
                        'content_line'    => (bool)data_get($block, 'content_line', false),
                        'link_text'       => data_get($block, 'link_text') ?: null,
                        'link_url'        => data_get($block, 'link_url') ?: null,
                        'col_start'       => $colStart,
                        'col_span'        => $colSpan,
                        'padding_top'     => max(0, min(300, (int)data_get($block, 'padding_top', 0))),
                        'padding_bottom'  => max(0, min(300, (int)data_get($block, 'padding_bottom', 0))),
                        'image'           => $imgUrl,
                        'image_alignment' => $imgAlignment,
                        'image_col_span'  => max(1, min(12, (int)data_get($block, 'image_col_span', 12))),
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

        $data['description_blocks'] = $descriptionBlocks;
        $data['description'] = $description;

        return $data;
    }
}
