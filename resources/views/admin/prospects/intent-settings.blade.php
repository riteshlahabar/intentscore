@extends('admin.layouts.app')
@section('title','Intent Scoring')
@section('page_title','Intent Scoring')
@section('page_subtitle','Points awarded for each prospect action. Saving recalculates every stored score.')

@section('content')
<form method="post" action="{{ route('admin.intent.settings.update') }}">
    @csrf @method('PUT')

    <div class="card table-card mb-3">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Activity</th><th style="width:130px">Score</th><th style="width:170px">Counted up to</th><th style="width:100px">Active</th></tr></thead>
                <tbody>
                @foreach($rules as $rule)
                    <tr>
                        <td><strong>{{ $rule->label }}</strong><div class="stat-mini">{{ $rule->event_key }}</div></td>
                        <td><input class="form-control form-control-sm" type="number" min="0" max="100" name="rules[{{ $rule->id }}][points]" value="{{ $rule->points }}"></td>
                        <td><input class="form-control form-control-sm" type="number" min="1" max="50" name="rules[{{ $rule->id }}][max_times]" value="{{ $rule->max_times }}" placeholder="unlimited"></td>
                        <td>
                            <div class="form-check form-switch">
                                <input type="hidden" name="rules[{{ $rule->id }}][is_active]" value="0">
                                <input class="form-check-input" type="checkbox" name="rules[{{ $rule->id }}][is_active]" value="1" @checked($rule->is_active)>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Intent levels</strong></div>
        <div class="card-body">
            <div class="row g-2">
                @foreach(['LOW'=>'0 – 10','ENGAGED'=>'11 – 25','INTERESTED'=>'26 – 50','HIGH INTENT'=>'51 and above'] as $level=>$range)
                    <div class="col-6 col-md-3">
                        <div class="metric-card">
                            <div class="metric-label">{{ $level }}</div>
                            <div style="font-size:16px;font-weight:700;margin-top:6px">{{ $range }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="stat-mini mt-2">Level bands are fixed for the MVP; only the points above are configurable.</div>
        </div>
    </div>

    <div class="text-end"><button class="btn btn-primary px-4" data-confirm="Save rules and recalculate all intent scores?">Save scoring rules</button></div>
</form>
@endsection
