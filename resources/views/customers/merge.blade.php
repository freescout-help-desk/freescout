@extends('layouts.admin')

@section('content')
<div class="container">
    <h2>Merge Customers</h2>
    <form action="{{ route('admin.customers.merge') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Primary Customer</label>
            <select name="customer1_id" class="form-control" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->full_name }} ({{ $customer->emails->first()->email }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Customer to Merge</label>
            <select name="customer2_id" class="form-control" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->full_name }} ({{ $customer->emails->first()->email }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Merge</button>
    </form>
</div>
@endsection
