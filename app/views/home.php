<?php include APP_PATH . '/views/partials/navbar.php'; ?>

<div class="content" id="content">
    <div class="container-fluid p-3 p-md-4">
        <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <h3 class="fw-bold text-primary m-3 mb-md-0">Dashboard </h2>
            <div class="action-buttons d-flex flex-column flex-md-row gap-2 w-md-auto">
                <button class="btn btn-primary btn-sm action-btn" onclick="window.location.href='<?php echo BASE_URL; ?>/orders/order_entry'">
                    <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">New Order</span>
                </button>
                <div class="dropdown w-30 w-md-auto">
                    <button class="btn btn-secondary action-btn btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-lightning"></i> <span class="d-none d-sm-inline">Quick Actions</span>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/customers/add_customer"><i class="bi bi-person-plus"></i> Add Customer</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/products/add_product"><i class="bi bi-box-seam"></i> Add Product</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/suppliers/add_supplier"><i class="bi bi-truck"></i> Add Supplier</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 g-md-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-card bg-white p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-muted mb-2">Total Customers</h5>
                            <h2 class="fw-bold"><?= $stats['customers'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-people card-icon text-primary"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-card bg-white p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-muted mb-2">Total Orders</h5>
                            <h2 class="fw-bold"><?= $stats['orders'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-clipboard-data card-icon text-success"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-card bg-white p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-muted mb-2">Total Products</h5>
                            <h2 class="fw-bold"><?= $stats['products'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-box-seam card-icon text-warning"></i>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="dashboard-card bg-white p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-muted mb-2">Total Suppliers</h5>
                            <h2 class="fw-bold"><?= $stats['suppliers'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-truck card-icon text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 mb-md-4">
                <h4 class="fw-bold mb-2 mb-md-0">Recent Orders</h4>
                <a href="#" class="btn btn-link text-decoration-none">View All →</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th class="d-none d-sm-table-cell">Arrival Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentOrders)): ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['customer']) ?></td>
                                    <td class="d-none d-sm-table-cell"><?= date('M d, Y', strtotime($order['arrival_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-success">Completed</span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> <span class="d-none d-sm-inline">View</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No recent orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Exchange Rates Dashboard -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="bg-white p-3 p-md-4 rounded-3 shadow-sm">
                    <h5 class="fw-bold mb-3">Exchange Rates (<?= date('Y-m-d'); ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Currency</th>
                                    <th>API Rate</th>
                                    <th>Manual Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($exchangeRates)): ?>
                                    <?php foreach ($exchangeRates as $code => $sources): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($code) ?></td>
                                            <td><?= isset($sources['CBSL']) ? number_format($sources['CBSL'], 6) : '-' ?></td>
                                            <td><?= isset($sources['Manual']) ? number_format($sources['Manual'], 6) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted">No rates available for today.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Section -->
        <div class="row mt-3 mt-md-4 g-3 g-md-4">
            <div class="col-12 col-lg-6">
                <div class="bg-white p-3 p-md-4 rounded-3 shadow-sm">
                    <h5 class="fw-bold mb-3">Order Trends</h5>
                    <div class="placeholder-glow">
                        <div class="placeholder col-12" style="height: 200px"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="bg-white p-3 p-md-4 rounded-3 shadow-sm">
                    <h5 class="fw-bold mb-3">Product Distribution</h5>
                    <div class="placeholder-glow">
                        <div class="placeholder col-12" style="height: 200px"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>