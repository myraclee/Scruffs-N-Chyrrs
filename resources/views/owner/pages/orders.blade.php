@extends('owner.layouts.owner_layout')

@section('page_css')
@vite(['resources/css/owner/pages/orders.css'])
@endsection

@section('content')
    <h1 class="page_header">Orders</h1>
    
    <div class="orders_container">
        <div class="orders_filter">
            <select class="filter_select" id="statusFilter">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="orders_table_container">
            <table class="orders_table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="empty_message">No orders yet</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
