<?php
    // Predefine any PHP data here

?>
<div class="sr-search">
    <div class="row">
        <div class="col-lg-8 order-lg-1 order-2">
            <form id="search-jobs" class="sr-search-form" action="" method="post">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="sr-keywords">Keywords</label>
                        <input id="sr-keywords" name="keywords" placeholder="Ex: Housekeeper">
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="sr-area">City, State or Zip</label>
                        <input id="sr-area" name="area" placeholder="Ex: Phoenix, AZ">
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="sr-distance">Distance</label>
                        <select id="sr-distance" name="distance">
                            <option value="9999">Any distance</option>
                            <option value="25">25 Miles</option>
                            <option value="50">50 Miles</option>
                            <option value="100">100 Miles</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-6 mb-3">
                        <label for="sr-distance"></label>
                        <button type="submit" class="sr-btn" action="search">Search</button>
                    </div>
                </div>
            </form>
            <div id="search-results" class="sr-results"></div>
            <div id="search-pagination" class="sr-pagination"></div>
        </div>
        <div class="col-lg-4 order-lg-2 order-1">
            <div id="search-filters" class="sr-filters">
                <h3>Filters</h3>
                <div class="group separate">
                    <label for="sr-department">Job Category</label>
                    <select id="sr-department" name="department">
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="group separate">
                    <span >Community</span>
                    <div id="sr-community" name="community">
                        <div class="group">
                            <input type="checkbox" id="All" name="All" value="all" checked>
                            <label for="All">All</label>
                        </div>
                    </div>
                </div>
                <label>Type</label>
                <div class="group">
                    <input id="sr-full-time" name="full-time" type="checkbox" checked>
                    <label for="sr-full-time">Full-time</label>
                </div>
                <div class="group">
                    <input id="sr-part-time" name="part-time" type="checkbox" checked>
                    <label for="sr-part-time">Part-time</label>
                </div>
                <div class="group">
                    <a class="sr-reset-filter" name="reset-filter">Reset filter</a>
                </div>
            </div>
        </div>
    </div>
</div>