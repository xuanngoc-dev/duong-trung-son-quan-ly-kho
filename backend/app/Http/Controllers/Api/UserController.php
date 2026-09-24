<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use App\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'start' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], $this->messages());

        $query = User::query()
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role));

        $total = (clone $query)->count();
        $users = $query
            ->latest()
            ->skip($filters['start'] ?? 0)
            ->take($filters['limit'] ?? 10)
            ->get()
            ->map(fn (User $user) => $this->payload($user));

        return response()->json([
            'success' => true,
            'data' => $users,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $user = User::create($this->validated($request));

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm nhân viên.',
            'data' => $this->payload($user),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật nhân viên.',
            'data' => $this->payload($user->refresh()),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa nhân viên.',
        ]);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'trang_thai' => ['required', Rule::enum(UserStatus::class)],
        ], $this->messages());
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Nhập họ tên.',
            'email.required' => 'Nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.required' => 'Nhập mật khẩu.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
            'role.required' => 'Chọn vai trò.',
            'role.enum' => 'Vai trò không hợp lệ.',
            'trang_thai.required' => 'Chọn trạng thái.',
            'trang_thai.enum' => 'Trạng thái không hợp lệ.',
            'start.integer' => 'Vị trí bắt đầu không hợp lệ.',
            'start.min' => 'Vị trí bắt đầu không hợp lệ.',
            'limit.integer' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.min' => 'Số lượng mỗi trang không hợp lệ.',
            'limit.max' => 'Số lượng mỗi trang tối đa 100.',
        ];
    }

    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
            'trang_thai' => $user->trang_thai instanceof UserStatus ? $user->trang_thai->value : $user->trang_thai,
            'created_at' => $user->created_at,
        ];
    }
}
