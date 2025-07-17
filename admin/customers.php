<?php
require_once '../config.php';

// Set page title
$page_title = 'Customers';

// Pagination settings
$results_per_page = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $results_per_page;

// Get total number of customers
$total_query = "SELECT COUNT(*) AS total FROM users_info";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_customers = $total_row ? (int)$total_row['total'] : 0;
$total_pages = max(1, ceil($total_customers / $results_per_page));

// Fetch paginated customers
$customers_query = "
    SELECT id, first_name, last_name, email, is_email_verified
    FROM users_info
    ORDER BY id ASC
    LIMIT $results_per_page OFFSET $offset
";
$customers_result = $conn->query($customers_query);

// Include header
include 'header.php';
?>
<style>
    .table td,
    .table th {
        padding-top: 0.7rem;
        padding-bottom: 0.7rem;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
    <h1 class="h2">Customers</h1>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="container-fluid">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Email Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                    <?php while ($customer = $customers_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['id']); ?></td>
                            <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td>
                                <?php if ($customer['is_email_verified']): ?>
                                    <span class="badge bg-success">Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Verified</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="bi bi-people" style="font-size: 2rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">No customers found.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <!-- Previous -->
                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>">Previous</a>
                </li>

                <!-- Page numbers -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Next -->
                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<?php
// Include footer
include 'footer.php';
?>
