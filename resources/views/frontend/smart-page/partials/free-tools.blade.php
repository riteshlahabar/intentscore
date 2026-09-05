@php($tools = $section->data['tools'] ?? ['revenue','no_show','profit'])

@if(in_array('revenue', $tools))
<div class="card border-0 shadow rounded p-4 mb-4" data-tool="revenue">
    <h5 class="mb-3">Grooming Revenue Calculator</h5>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Number of groomers</label><input type="number" min="0" step="1" value="2" class="form-control" data-in="groomers"></div>
        <div class="col-md-3"><label class="form-label">Appointments per day</label><input type="number" min="0" step="1" value="6" class="form-control" data-in="appts"></div>
        <div class="col-md-3"><label class="form-label">Average ticket (₹)</label><input type="number" min="0" step="1" value="1200" class="form-control" data-in="ticket"></div>
        <div class="col-md-3"><label class="form-label">Working days / month</label><input type="number" min="0" max="31" step="1" value="26" class="form-control" data-in="days"></div>
    </div>
    <div><button type="button" class="btn btn-primary mt-3" data-calc>Calculate</button></div>
    <div class="row g-3 mt-1" data-calc-out hidden>
        <div class="col-md-4"><div class="text-muted small">Estimated daily revenue</div><div class="h5 text-primary mb-0" data-out="daily"></div></div>
        <div class="col-md-4"><div class="text-muted small">Monthly revenue</div><div class="h5 text-primary mb-0" data-out="monthly"></div></div>
        <div class="col-md-4"><div class="text-muted small">Annual revenue</div><div class="h5 text-primary mb-0" data-out="annual"></div></div>
    </div>
</div>
@endif

@if(in_array('no_show', $tools))
<div class="card border-0 shadow rounded p-4 mb-4" data-tool="no_show">
    <h5 class="mb-3">No-Show Calculator</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Monthly appointments</label><input type="number" min="0" step="1" value="300" class="form-control" data-in="appts"></div>
        <div class="col-md-4"><label class="form-label">Average ticket (₹)</label><input type="number" min="0" step="1" value="1200" class="form-control" data-in="ticket"></div>
        <div class="col-md-4"><label class="form-label">No-show %</label><input type="number" min="0" max="100" step="0.1" value="8" class="form-control" data-in="rate"></div>
    </div>
    <div><button type="button" class="btn btn-primary mt-3" data-calc>Calculate</button></div>
    <div class="row g-3 mt-1" data-calc-out hidden>
        <div class="col-md-6"><div class="text-muted small">Estimated monthly revenue lost</div><div class="h5 text-primary mb-0" data-out="monthly"></div></div>
        <div class="col-md-6"><div class="text-muted small">Estimated annual revenue lost</div><div class="h5 text-primary mb-0" data-out="annual"></div></div>
    </div>
</div>
@endif

@if(in_array('profit', $tools))
<div class="card border-0 shadow rounded p-4 mb-4" data-tool="profit">
    <h5 class="mb-3">Business Profit Calculator</h5>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Monthly revenue (₹)</label><input type="number" min="0" step="1" value="200000" class="form-control" data-in="revenue"></div>
        <div class="col-md-3"><label class="form-label">Payroll (₹)</label><input type="number" min="0" step="1" value="80000" class="form-control" data-in="payroll"></div>
        <div class="col-md-3"><label class="form-label">Rent (₹)</label><input type="number" min="0" step="1" value="30000" class="form-control" data-in="rent"></div>
        <div class="col-md-3"><label class="form-label">Supplies (₹)</label><input type="number" min="0" step="1" value="20000" class="form-control" data-in="supplies"></div>
        <div class="col-md-3"><label class="form-label">Software (₹)</label><input type="number" min="0" step="1" value="5000" class="form-control" data-in="software"></div>
        <div class="col-md-3"><label class="form-label">Marketing (₹)</label><input type="number" min="0" step="1" value="10000" class="form-control" data-in="marketing"></div>
        <div class="col-md-3"><label class="form-label">Other expenses (₹)</label><input type="number" min="0" step="1" value="8000" class="form-control" data-in="other"></div>
    </div>
    <div><button type="button" class="btn btn-primary mt-3" data-calc>Calculate</button></div>
    <div class="row g-3 mt-1" data-calc-out hidden>
        <div class="col-md-4"><div class="text-muted small">Estimated monthly profit</div><div class="h5 text-primary mb-0" data-out="monthly"></div></div>
        <div class="col-md-4"><div class="text-muted small">Profit margin</div><div class="h5 text-primary mb-0" data-out="margin"></div></div>
        <div class="col-md-4"><div class="text-muted small">Annual profit</div><div class="h5 text-primary mb-0" data-out="annual"></div></div>
    </div>
</div>
@endif
