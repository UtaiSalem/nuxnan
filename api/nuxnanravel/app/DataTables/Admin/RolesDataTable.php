<?php

namespace App\DataTables\Admin;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RolesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('users_count', function ($role) {
                return $role->users_count ?? 0;
            })
            ->addColumn('permissions_count', function ($role) {
                return $role->permissions_count ?? 0;
            })
            ->addColumn('permissions_list', function ($role) {
                return $role->permissions->take(5)->pluck('name')->toArray();
            })
            ->addColumn('status_badge', function ($role) {
                if ($role->status) {
                    return [
                        'label' => 'เปิดใช้งาน',
                        'color' => 'bg-green-500',
                    ];
                }

                return [
                    'label' => 'ปิดใช้งาน',
                    'color' => 'bg-gray-500',
                ];
            })
            ->addColumn('is_protected', function ($role) {
                return in_array($role->name, ['SUPER_ADMIN', 'ADMIN', 'STUDENT']);
            })
            ->editColumn('created_at', function ($role) {
                return $role->created_at?->format('Y-m-d H:i');
            })
            ->rawColumns(['status_badge'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Role $model): QueryBuilder
    {
        return $model->newQuery()
            ->withCount(['users', 'permissions'])
            ->with('permissions')
            ->select('roles.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('roles-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
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
            Column::make('name')->title('ชื่อ Role'),
            Column::make('display_name')->title('ชื่อแสดง'),
            Column::make('description')->title('คำอธิบาย'),
            Column::make('users_count')->title('ผู้ใช้')->orderable(false)->searchable(false),
            Column::make('permissions_count')->title('สิทธิ์')->orderable(false)->searchable(false),
            Column::make('status_badge')->title('สถานะ')->orderable(false)->searchable(false),
            Column::make('created_at')->title('สร้างเมื่อ'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Roles_'.date('YmdHis');
    }
}
