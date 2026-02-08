@extends('layouts.app')

@section('title', 'Server Monitoring')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @livewire('server-monitoring.server-monitoring-table')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
@endpush