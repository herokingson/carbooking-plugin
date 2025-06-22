 <div class="bg-white backdrop-blur-[5px] search-form rounded-lg p-6 mb-12">
     <h2 class="text-4xl font-semibold text-gray-800 mb-6">ค้นหารถที่ว่าง</h2>
     <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

         <!-- เลือกรถ -->
         <div>
             <label class="block text-gray-700 font-medium mb-2">ประเภทรถ</label>
             <select name="vehicle_id" required class="dropdown-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                 <option value="">เลือกรถ</option>
                 <?php foreach ($vehicles as $v): ?>
                     <option value="<?= esc_attr($v->id); ?>"
                         <?= (isset($_GET['vehicle_id']) && $_GET['vehicle_id'] == $v->id) ? 'selected' : ''; ?>>
                         <?= esc_html($v->name); ?>
                     </option>
                 <?php endforeach; ?>
             </select>
         </div>
         <!-- เลือกต้นทาง -->
         <div>
            <label class="block text-gray-700 font-medium mb-2">ต้นทาง</label>
             <select name="from" required class="dropdown-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-50">
                 <option value="">เลือกต้นทาง</option>
                 <?php
                    $froms = array_unique(array_map(fn($r) => $r->from_location, $routes));
                    foreach ($froms as $from) {
                        $selected = (isset($_GET['from']) && $_GET['from'] === $from) ? 'selected' : '';
                        echo "<option value='" . esc_attr($from) . "' $selected>" . esc_html($from) . "</option>";
                    }
                    ?>
             </select>
         </div>
         <!-- เลือกปลายทาง -->
         <div>
            <label class="block text-gray-700 font-medium mb-2">ปลายทาง</label>
             <select name="to" required class="dropdown-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                 <option value="">เลือกปลายทาง</option>
                 <?php
                    $tos = array_unique(array_map(fn($r) => $r->to_location, $routes));
                    foreach ($tos as $to) {
                        $selected = (isset($_GET['to']) && $_GET['to'] === $to) ? 'selected' : '';
                        echo "<option value='" . esc_attr($to) . "' $selected>" . esc_html($to) . "</option>";
                    }
                    ?>
             </select>
         </div>
         <!-- เวลาเริ่มต้น -->
         <div>
             <label class="block text-gray-700 font-medium mb-2">เลือกวันที่</label>
             <input type="datetime-local" name="start_time" class="dropdown-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                 value="<?= isset($_GET['start_time']) ? esc_attr($_GET['start_time']) : ''; ?>" required>
         </div>
         <div class="flex items-end">
            
             <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition flex items-center justify-center"> <i class="fas fa-search mr-2"></i> ค้นหารถว่าง</button>
         </div>
     </form>
 </div>