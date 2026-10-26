<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var array<string, array<string, mixed>> $trainingMasters */
        $trainingMasters = config('training.masters');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'training' => [
                'exercise' => is_file(storage_path('app/training-exercise')),
                'title' => config('training.title'), 'homeUrl' => route('worklogs.index'),
                'masters' => collect($trainingMasters)->filter(fn ($value, $key) => $key !== 'accounts' || $request->user()?->can('manage-training'))->map(fn ($value, $key) => ['title' => $value['title'], 'url' => route('masters.index', ['resource' => $key])])->values(),
            ],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
