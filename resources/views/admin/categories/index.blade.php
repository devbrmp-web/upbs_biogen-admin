@extends('layouts.vertical', ['title' => 'Categories List', 'subTitle' => 'Management'])

@section('content')

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3">
                    <div class="search-bar">
                        <span><i class="bx bx-search-alt"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search ..." />
                    </div>
                </div>
                <!-- end row -->
            </div>
            <div>
                <div class="table-responsive table-centered">
                    <table class="table text-nowrap mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @foreach($categories as $c)
                            <tr>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->slug }}</td>
                                <td>{{ $c->description }}</td>
                                <td>{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $c->updated_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection