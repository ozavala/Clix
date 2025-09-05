@extends('layouts.app')

@section('title', 'Create New Quotation')

@section('content')
<div class="container">
    <h1>{{ __('quotations.Create New Quotation') }}</h1>

    <form action="{{ route('quotations.store') }}" method="POST">
        @include('quotations._form', [
            'quotation' => new \App\Models\Quotation(),
            'statuses' => $statuses,
            'opportunities' => $opportunities,
            'products' => $products,
            'selectedOpportunity' => $selectedOpportunity ?? null
        ])
    </form>
</div>
@endsection