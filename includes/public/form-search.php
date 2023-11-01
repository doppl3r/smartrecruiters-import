<?php
    // Predefine any PHP data here

?>
<div class="idx-sr-search">
    <div class="row">
        <div class="col-lg-8 order-lg-1 order-2">
            <form id="search-jobs" class="idx-sr-search-form" action="" method="post">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="idx-sr-keywords">Keywords</label>
                        <input id="idx-sr-keywords" name="keywords" placeholder="Ex: Housekeeper">
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="idx-sr-area">City, State or Zip</label>
                        <input id="idx-sr-area" name="area" placeholder="Ex: Phoenix, AZ">
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="idx-sr-distance">Distance</label>
                        <select id="idx-sr-distance" name="distance">
                            <option value="9999">Any distance</option>
                            <option value="25">25 Miles</option>
                            <option value="50">50 Miles</option>
                            <option value="100">100 Miles</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="idx-sr-distance"></label>
                        <button type="submit" class="idx-sr-btn" action="search">Search</button>
                    </div>
                </div>
            </form>
            <div id="search-results" class="idx-sr-results"></div>
            <div id="search-pagination" class="idx-sr-pagination"></div>
        </div>
        <div class="col-lg-4 order-lg-2 order-1">
            <div id="search-filters" class="idx-sr-filters">
                <h3>Filters</h3>
                <div class="group separate">
                    <label for="idx-sr-department">Job Department</label>
                    <select id="idx-sr-department" name="department">
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="group separate">
                    <label for="idx-sr-community">Community</label>
                    <select id="idx-sr-community" name="community">
                        <option value="all">All</option>
                    </select>
                </div>
                <label>Type</label>
                <div class="group">
                    <input id="idx-sr-full-time" name="full-time" type="checkbox" checked>
                    <label for="idx-sr-full-time">Full-time</label>
                </div>
                <div class="group">
                    <input id="idx-sr-part-time" name="part-time" type="checkbox" checked>
                    <label for="idx-sr-part-time">Part-time</label>
                </div>
                <div class="group">
                    <a class="idx-sr-reset-filter" name="reset-filter">Reset filter</a>
                </div>
            </div>
        </div>
    </div>
</div>