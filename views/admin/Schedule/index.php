<div class="container mt-4">

    <div class="d-flex justify-content-between mb-3">
        <h3>Lịch khởi hành</h3>
        <a href="index.php?act=admin-schedule-create" class="btn btn-primary">+ Tạo lịch</a>
    </div>

    <div class="card shadow-sm">
        <table class="table table-bordered table-striped mb-0">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Tour</th>
                    <th>Ngày đi</th>
                    <th>Ngày về</th>
                    <th>Ghế / Còn</th>
                    <th>Giá người lớn</th>
                    <th>Giá trẻ em (dưới 10 tuổi)</th>
                    <th>Trạng thái</th>
                    <th width="15%">Hành động</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($schedules as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><?= $s['tour_title'] ?></td>
                    <td><?= $s['depart_date'] ?></td>
                    <td><?= $s['return_date'] ?></td>
                    <td><?= $s['seats_available'] ?> / <?= $s['seats_total'] ?></td>

                    <td><?= number_format($s['price_adult'] ?: 0) . "đ" ?></td>
                    <td><?= number_format($s['price_children'] ?: 0) . "đ" ?></td>

                    <td>
                        <span class="badge badge-info"><?= $s['status'] ?></span>
                    </td>
                    <td>
                        <a href="index.php?act=admin-schedule-edit&id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
                        <a onclick="return confirm('Xóa lịch này?')" 
                           href="index.php?act=admin-schedule-delete&id=<?= $s['id'] ?>" 
                           class="btn btn-danger btn-sm">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>
