<?php

namespace App\DataTables\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UsersDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('avatar', function ($user) {
                return $user->avatar ?? '/storage/images/default-avatar.png';
            })
            ->addColumn('roles', function ($user) {
                return $user->roles->pluck('name')->toArray();
            })
            ->addColumn('roles_display', function ($user) {
                return $user->roles->map(function ($role) {
                    $colors = [
                        'SUPER_ADMIN' => 'bg-red-500',
                        'ADMIN' => 'bg-purple-500',
                        'MODERATOR' => 'bg-blue-500',
                        'INSTRUCTOR' => 'bg-green-500',
                        'STUDENT' => 'bg-gray-500',
                    ];
                    $color = $colors[$role->name] ?? 'bg-gray-500';

                    return [
                        'name' => $role->name,
                        'display_name' => $role->display_name ?? $role->name,
                        'color' => $color,
                    ];
                });
            })
            ->addColumn('status', function ($user) {
                if ($user->email_verified_at) {
                    return 'verified';
                }

                return 'unverified';
            })
            ->addColumn('status_badge', function ($user) {
                if ($user->email_verified_at) {
                    return [
                        'label' => 'ยืนยันแล้ว',
                        'color' => 'bg-green-500',
                    ];
                }

                return [
                    'label' => 'รอยืนยัน',
                    'color' => 'bg-yellow-500',
                ];
            })
            ->editColumn('created_at', function ($user) {
                return $user->created_at?->format('Y-m-d H:i');
            })
            ->editColumn('email_verified_at', function ($user) {
                return $user->email_verified_at?->format('Y-m-d H:i');
            })
            ->filterColumn('roles', function ($query, $keyword) {
                $query->whereHas('roles', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('status', function ($query, $keyword) {
                if ($keyword === 'verified') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($keyword === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            })
            ->rawColumns(['avatar', 'roles_display', 'status_badge'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        return $model->newQuery()
            ->with('roles')
            ->select('users.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('users-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'processing' => true,
                'serverSide' => true,
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->title('ID'),
            Column::make('avatar')->title('รูป')->orderable(false)->searchable(false),
            Column::make('name')->title('ชื่อ'),
            Column::make('email')->title('อีเมล'),
            Column::make('phone_number')->title('เบอร์โทร'),
            Column::make('roles_display')->title('บทบาท')->orderable(false),
            Column::make('status_badge')->title('สถานะ')->orderable(false),
            Column::make('created_at')->title('สมัครเมื่อ'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Users_'.date('YmdHis');
    }
}
