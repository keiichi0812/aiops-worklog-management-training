<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MasterController extends Controller
{
    /** @return array<string, mixed> */
    private function definition(string $resource): array
    {
        $definitions = config('training.masters');
        abort_unless(is_array($definitions) && isset($definitions[$resource]), 404);

        return $definitions[$resource];
    }

    public function index(Request $request, string $resource): Response|JsonResponse
    {
        $definition = $this->definition($resource);
        if ($resource === 'accounts') {
            Gate::authorize('manage-training');
        }
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        /** @var class-string<Model> $class */
        $class = $definition['model'];
        $query = $class::query();
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q')->toString().'%');
        }
        $records = $query->orderBy('id')->paginate(30)->withQueryString();
        if ($resource === 'accounts') {
            $records->through(fn (Model $user): array => [
                'id' => $user->getKey(), 'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'), 'role' => $user->getAttribute('role'),
            ]);
        }
        $props = [
            'title' => $definition['title'], 'records' => $records,
            'fields' => $definition['fields'], 'columns' => $definition['columns'],
            'filters' => $filters, 'canManage' => $request->user()->can('manage-training'),
            'baseUrl' => route('masters.index', ['resource' => $resource]),
            'options' => $resource === 'projects' ? ['customer_id' => Customer::orderBy('name')->get(['id', 'name'])] : [],
            'summary' => [],
        ];

        return $request->expectsJson() ? response()->json($props) : Inertia::render('training/index', $props);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        Gate::authorize('manage-training');
        $definition = $this->definition($resource);
        /** @var class-string<Model> $class */
        $class = $definition['model'];
        $record = new $class;
        $this->save($request, $record, $resource);

        return to_route('masters.index', ['resource' => $resource]);
    }

    public function update(Request $request, string $resource, int $id): RedirectResponse
    {
        Gate::authorize('manage-training');
        $definition = $this->definition($resource);
        /** @var class-string<Model> $class */
        $class = $definition['model'];
        $this->save($request, $class::findOrFail($id), $resource);

        return to_route('masters.index', ['resource' => $resource]);
    }

    private function save(Request $request, Model $record, string $resource): void
    {
        $rules = ['name' => ['required', 'string', 'max:150']];
        $table = $record->getTable();
        if ($resource === 'accounts') {
            $rules += [
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($record)],
                'password' => [$record->exists ? 'nullable' : 'required', 'string', 'min:12', 'max:200'],
                'role' => ['required', Rule::in(['admin', 'user'])],
            ];
        } else {
            $rules += ['code' => ['required', 'string', 'max:40', Rule::unique($table)->ignore($record)], 'notes' => ['nullable', 'string', 'max:2000']];
            if ($resource === 'customers' && config('training.topic') === 'license') {
                $rules += ['contact_name' => ['nullable', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:255']];
            }
            if ($resource === 'products') {
                $rules += ['description' => ['nullable', 'string', 'max:2000']];
            }
            if ($resource === 'projects') {
                $rules += ['customer_id' => ['required', 'integer', 'exists:customers,id'], 'division' => ['required', Rule::in(['共通', '東京', '豊橋'])]];
            }
        }
        $data = $request->validate($rules);
        if ($resource === 'accounts') {
            if ($record->getKey() === $request->user()->getKey() && $data['role'] !== 'admin') {
                throw ValidationException::withMessages(['role' => '自分の管理者権限は変更できません。']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $data['email_verified_at'] = now();
        }
        $record->forceFill($data)->save();
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        Gate::authorize('manage-training');
        $definition = $this->definition($resource);
        /** @var class-string<Model> $class */
        $class = $definition['model'];
        $record = $class::findOrFail($id);
        if ($resource === 'accounts' && $record->getKey() === $request->user()->getKey()) {
            throw ValidationException::withMessages(['resource' => '自分のアカウントはここでは削除できません。']);
        }
        $record->delete();

        return to_route('masters.index', ['resource' => $resource]);
    }
}
