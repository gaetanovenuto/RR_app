<form class="d-flex justify-content-between align-items-center flex-wrap gap-2" id="filter_search_form" method="POST" onkeydown="return event.key != 'Enter';">
    <div class="d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
        <label for="search_filter" class="fw-semibold text-muted">Ricerca:</label>
        <input type="text" class="form-control form-control-sm me-4" name="search_filter" placeholder="Cosa cerchi?" id="search_text">
    </div>
    <div class="input_start d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
        <label for="starting_date" class="fw-semibold me-1">Da:</label>
        <input type="date" name="starting_date" class="col-auto form-control-style text-muted" id="starting_date">
    </div>
    <div class="input_end d-flex justify-content-between align-items-center me-1 col-12 col-md-auto">
        <label for="ending_date" class="fw-semibold me-1">A:</label>
        <input type="date" name="ending_date" class="form-control-style text-muted" id="ending_date">
    </div>
    <button type="button" class="btn-secondary btn btn-sm mx-2" onclick="reloadTable()">Cerca</button>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetFilters()">Reset filtri</button>
</form>
<div class="tags mt-2"></div>