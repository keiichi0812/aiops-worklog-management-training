<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\WorkLogReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkLogController extends Controller
{
    public function index(Request $request, WorkLogReport $report): Response|JsonResponse
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'], 'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'], 'user_id' => ['nullable', 'integer'],
            'division' => ['nullable', Rule::in(['共通', '東京', '豊橋'])],
        ]);
        $month = $request->filled('month') ? $request->string('month')->toString() : today()->format('Y-m');
        $filters['month'] = $month;
        $query = WorkLog::visibleTo($request->user())->with(['project.customer', 'user'])
            ->where('worked_on', '>=', $month.'-01')->where('worked_on', '<', Carbon::parse($month.'-01')->addMonth()->toDateString());
        foreach (['project_id', 'user_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->integer($field));
            }
        }
        if ($request->filled('customer_id')) {
            $query->whereHas('project', fn ($q) => $q->where('customer_id', $request->integer('customer_id')));
        }
        if ($request->filled('division')) {
            $query->whereHas('project', fn ($q) => $q->where('division', $request->string('division')->toString()));
        }
        $logs = (clone $query)->get();
        $summary = $logs->groupBy(fn (WorkLog $log): string => $log->project_id.' / '.$log->user_id)
            ->map(fn ($items): array => ['label' => $items->first()->project->name.' / '.$items->first()->user->name,
                'hours' => $report->hours((int) $items->sum('half_hours'))])->values();
        $dailySummary = $logs->sortByDesc('worked_on')->groupBy(fn (WorkLog $log): string => $log->worked_on->toDateString().' / '.$log->user_id)
            ->map(fn ($items): array => ['date' => $items->first()->worked_on->toDateString(), 'owner' => $items->first()->user->name,
                'hours' => $report->hours((int) $items->sum('half_hours'))])->values();
        $props = [
            'title' => '工数管理', 'baseUrl' => route('worklogs.index'), 'fields' => config('training.fields'),
            'columns' => config('training.columns'), 'filters' => $filters, 'canManage' => true,
            'records' => $query->orderByDesc('worked_on')->orderByDesc('id')->paginate(30)->withQueryString()->through(fn (WorkLog $log): array => $this->row($log, $report)),
            'options' => ['project_id' => Project::orderBy('code')->get()->map(fn (Project $project): array => ['id' => $project->id, 'name' => $project->code.'：'.$project->name]),
                'customer_id' => Customer::orderBy('name')->get(['id', 'name']),
                'user_id' => User::when(! $request->user()->isAdmin(), fn ($q) => $q->whereKey($request->user()->id))->get(['id', 'name'])],
            'summary' => $summary, 'topic' => 'worklog', 'today' => today()->toDateString(), 'totalHours' => $report->hours((int) $logs->sum('half_hours')), 'dailySummary' => $dailySummary,
        ];

        return $request->expectsJson() ? response()->json($props) : Inertia::render('training/index', $props);
    }

    /** @return array<string, mixed> */
    private function row(WorkLog $log, WorkLogReport $report): array
    {
        return [...$log->only(['id', 'project_id', 'user_id', 'description']), 'worked_on' => $log->worked_on->toDateString(),
            'hours' => $report->hours($log->half_hours), 'project' => $log->project->code.'：'.$log->project->name,
            'customer' => $log->project->customer->name, 'division' => $log->project->division, 'owner' => $log->user->name];
    }

    public function show(Request $request, int $id, WorkLogReport $report): Response|JsonResponse
    {
        $log = WorkLog::visibleTo($request->user())->with(['project.customer', 'user'])->findOrFail($id);
        $props = ['title' => '工数 #'.$log->id, 'record' => $this->row($log, $report), 'backUrl' => route('worklogs.index')];

        return $request->expectsJson() ? response()->json($props) : Inertia::render('training/show', $props);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        WorkLog::create($data);

        return to_route('worklogs.index');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $log = WorkLog::visibleTo($request->user())->findOrFail($id);
        $log->update($this->validated($request));

        return to_route('worklogs.index');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        WorkLog::visibleTo($request->user())->findOrFail($id)->delete();

        return to_route('worklogs.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'], 'worked_on' => ['required', 'date_format:Y-m-d'],
            'hours' => ['required', 'numeric', 'min:0.5', 'max:1000000', 'multiple_of:0.5'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], ['hours.multiple_of' => '作業時間は0.5時間刻みで入力してください。', 'hours.min' => '作業時間は0.5時間以上で入力してください。']);
        $data['half_hours'] = (int) round((float) $data['hours'] * 2);
        unset($data['hours']);

        return $data;
    }
}
