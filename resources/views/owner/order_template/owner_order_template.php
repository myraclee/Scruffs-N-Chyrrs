<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Owner - Order Template</title>
<link rel="stylesheet" href="../../owner/order_template/owner_order_template.css">
</head>
<body>

<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">

        <img src="../../photos/brand_elements/label_name.png"
             class="sidebar-logo">

        <ul class="sidebar-menu">
            <li>Dashboard</li>
            <li>Inventory</li>
            <li>Orders</li>
            <li class="active">Content</li>
        </ul>

        <div class="owner-profile">
            <p><strong>Celina Felizardo</strong></p>
            <span>celinafelizardo@gmail.com</span>
        </div>

    </aside>


    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">

        <h1 class="page-title">Content Management</h1>

        <div class="tabs">
            <button class="tab-btn">Home Page</button>
            <button class="tab-btn">Products</button>
            <button class="tab-btn active-tab">Order Template</button>
        </div>

        <h2 class="section-title">Order Template</h2>

        <!-- ===== TEMPLATE TABLE ===== -->
        <div class="template-table">

            <div class="table-header">
                <span>Product Category</span>
                <span>Lamination Options</span>
                <span>Options Label</span>
                <span>Options Selection</span>
                <span>Discount Description</span>
                <span>Discount Rate</span>
                <span>Actions</span>
            </div>

            <!-- ROW 1 -->
            <div class="table-row">
                <span>Stickers</span>
                <span>Matte, Glossy, Glitter, Holo Rainbow</span>
                <span>Sticker Type</span>
                <span>Die-Cut, Kiss-Cut</span>
                <span>5 peso per sheet for 7 sheets+</span>
                <span>-Php 5 / qty ≥ 7</span>
                <div class="action-buttons">
                    <button class="edit-btn">Edit</button>
                    <button class="delete-btn">Delete</button>
                </div>
            </div>

            <!-- ROW 2 -->
            <div class="table-row">
                <span>Button Pins</span>
                <span>Matte, Glossy, Glitter</span>
                <span>Button Pin Size</span>
                <span>1x1, 1.5x1.5, 2x2</span>
                <span>3 peso discount per item for 10+</span>
                <span>-Php 3 / qty ≥ 10</span>
                <div class="action-buttons">
                    <button class="edit-btn">Edit</button>
                    <button class="delete-btn">Delete</button>
                </div>
            </div>

        </div>

        <button class="add-btn">Add New Template</button>

    </main>

</div>

</body>
</html>