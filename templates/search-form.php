<form method="GET" class="flex flex-col p-[0.5rem] mb-[0.75rem] gap-2 max-w-xl">

    <!-- เลือกรถ -->
    <select name="vehicle_id" required class="select border p-2">
        <option value="">เลือกรถ</option>
        <?php foreach ($vehicles as $v): ?>
        <option value="<?= esc_attr($v->id); ?>"
            <?= (isset($_GET['vehicle_id']) && $_GET['vehicle_id'] == $v->id) ? 'selected' : ''; ?>>
            <?= esc_html($v->name); ?>
        </option>
        <?php endforeach; ?>
    </select>

    <!-- เลือกต้นทาง -->
    <select name="from" required class="select border p-2">
        <option value="">เลือกต้นทาง</option>
        <?php
        $froms = array_unique(array_map(fn($r) => $r->from_location, $routes));
        foreach ($froms as $from) {
            $selected = (isset($_GET['from']) && $_GET['from'] === $from) ? 'selected' : '';
            echo "<option value='" . esc_attr($from) . "' $selected>" . esc_html($from) . "</option>";
        }
        ?>
    </select>

    <!-- เลือกปลายทาง -->
    <select name="to" required class="select border p-2">
        <option value="">เลือกปลายทาง</option>
        <?php
        $tos = array_unique(array_map(fn($r) => $r->to_location, $routes));
        foreach ($tos as $to) {
            $selected = (isset($_GET['to']) && $_GET['to'] === $to) ? 'selected' : '';
            echo "<option value='" . esc_attr($to) . "' $selected>" . esc_html($to) . "</option>";
        }
        ?>
    </select>

    <!-- เวลาเริ่มต้น -->
    <input type="datetime-local" name="start_time" class="select border p-2"
        value="<?= isset($_GET['start_time']) ? esc_attr($_GET['start_time']) : ''; ?>" required>

    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">ค้นหารถว่าง</button>
</form>