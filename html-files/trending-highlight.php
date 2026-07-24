<style>
/* Wrapper */
.trending_wrapper {
    display: flex;
    align-items: center;
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    padding: 8px 10px;
    background: #fff;
}

/* Fixed title */
.trending-list-title {
    flex: 0 0 auto;
    margin-right: 15px;
}

.trending-list-title p {
    color: red;
    font-weight: 600;
    margin: 0;
    font-family: 'lato';
    white-space: nowrap;
}

/* Scrollable items */
.trending_scroll {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    white-space: nowrap;
    flex: 1;
}

/* Hide scrollbar */
.trending_scroll::-webkit-scrollbar {
    display: none;
}

.trending_item {
    flex: 0 0 auto;
}

.trending_item a {
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-family: 'lato';
    white-space: nowrap;
}

.trending_item a:hover {
    color: red;
}



</style>


<div class="trending_wrapper">

    <div class="trending-list-title">
        <p>Trending</p>
    </div>

    <div class="trending_scroll">
        <div class="trending_item"><a href="#">Ecopreneur</a></div>
        <div class="trending_item"><a href="#">US Iran War</a></div>
        <div class="trending_item"><a href="#">Tamil Nadu Election 2026</a></div>
        <div class="trending_item"><a href="#">Parentology</a></div>
        <div class="trending_item"><a href="#">AP SSC Result</a></div>
        <div class="trending_item"><a href="#">Raghav Chadha</a></div>
    </div>

</div>