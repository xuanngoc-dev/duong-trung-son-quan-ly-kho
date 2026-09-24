<script setup>
import { computed, reactive, ref } from 'vue'

const keyword = ref('')
const category = ref('')
const dialogOpen = ref(false)
const form = reactive({ code: '', name: '', category: '', unit: 'Cái', minimum: 10 })

const products = ref([
  { code: 'VT-00124', name: 'Ống PVC Bình Minh Ø90', category: 'Ống nhựa', unit: 'Cây', stock: 128, price: 186000, status: 'active' },
  { code: 'VT-00318', name: 'Co nối ren trong 27', category: 'Phụ kiện ống', unit: 'Cái', stock: 12, price: 28000, status: 'low' },
  { code: 'VT-00542', name: 'Van bi đồng D34', category: 'Van khóa', unit: 'Cái', stock: 4, price: 215000, status: 'low' },
  { code: 'VT-00611', name: 'Băng tan PTFE 12mm', category: 'Vật tư phụ', unit: 'Cuộn', stock: 240, price: 6500, status: 'active' },
  { code: 'VT-00709', name: 'Máy bơm tăng áp 200W', category: 'Máy bơm', unit: 'Bộ', stock: 18, price: 1480000, status: 'active' },
  { code: 'VT-00882', name: 'Keo dán ống PVC 500g', category: 'Vật tư phụ', unit: 'Lon', stock: 0, price: 92000, status: 'empty' },
])

const filteredProducts = computed(() => {
  const search = keyword.value.trim().toLowerCase()
  return products.value.filter(
    (item) =>
      (!category.value || item.category === category.value) &&
      (!search || `${item.code} ${item.name}`.toLowerCase().includes(search)),
  )
})

const money = (value) => new Intl.NumberFormat('vi-VN').format(value)

function addProduct() {
  products.value.unshift({
    ...form,
    stock: 0,
    price: 0,
    status: 'empty',
  })
  Object.assign(form, { code: '', name: '', category: '', unit: 'Cái', minimum: 10 })
  dialogOpen.value = false
}
</script>

<template>
  <div class="page-list">
    <div class="page-heading">
      <div>
        <h2>Danh mục hàng hóa</h2>
        <p>Quản lý mã hàng, nhóm hàng, đơn vị và định mức tồn kho.</p>
      </div>
      <el-button type="primary" @click="dialogOpen = true">
        <el-icon><Plus /></el-icon>
        Thêm hàng hóa
      </el-button>
    </div>

    <el-card shadow="never">
      <div class="toolbar">
        <el-input v-model="keyword" clearable placeholder="Tìm theo mã hoặc tên hàng...">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-select v-model="category" clearable placeholder="Tất cả nhóm hàng">
          <el-option
            v-for="item in ['Ống nhựa', 'Phụ kiện ống', 'Van khóa', 'Vật tư phụ', 'Máy bơm']"
            :key="item"
            :label="item"
            :value="item"
          />
        </el-select>
        <el-button><el-icon><Filter /></el-icon>Bộ lọc</el-button>
        <div class="toolbar-spacer" />
        <el-button><el-icon><Upload /></el-icon>Nhập Excel</el-button>
        <el-button><el-icon><Download /></el-icon>Xuất Excel</el-button>
      </div>

      <el-table :data="filteredProducts" stripe>
        <el-table-column type="selection" width="46" />
        <el-table-column prop="code" label="Mã hàng" width="130" fixed />
        <el-table-column prop="name" label="Tên hàng hóa" min-width="230">
          <template #default="{ row }">
            <div class="product-cell">
              <div class="product-image"><el-icon><Goods /></el-icon></div>
              <div><strong>{{ row.name }}</strong><small>{{ row.category }}</small></div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="unit" label="ĐVT" width="90" />
        <el-table-column prop="stock" label="Tồn kho" width="110" align="right">
          <template #default="{ row }">
            <strong :class="{ danger: row.stock < 15 }">{{ row.stock }}</strong>
          </template>
        </el-table-column>
        <el-table-column label="Giá nhập" width="140" align="right">
          <template #default="{ row }">{{ money(row.price) }} ₫</template>
        </el-table-column>
        <el-table-column label="Trạng thái" width="130">
          <template #default="{ row }">
            <el-tag v-if="row.status === 'active'" type="success" effect="light">Đang kinh doanh</el-tag>
            <el-tag v-else-if="row.status === 'low'" type="warning" effect="light">Sắp hết</el-tag>
            <el-tag v-else type="danger" effect="light">Hết hàng</el-tag>
          </template>
        </el-table-column>
        <el-table-column width="100" align="right" fixed="right">
          <template #default>
            <el-button text circle><el-icon><Edit /></el-icon></el-button>
            <el-button text circle><el-icon><MoreFilled /></el-icon></el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <span>Hiển thị {{ filteredProducts.length }} / {{ products.length }} hàng hóa</span>
        <el-pagination layout="prev, pager, next" :total="products.length" :page-size="10" />
      </div>
    </el-card>

    <el-dialog v-model="dialogOpen" title="Thêm hàng hóa" width="min(560px, 94vw)">
      <el-form label-position="top">
        <el-row :gutter="14">
          <el-col :span="10"><el-form-item label="Mã hàng"><el-input v-model="form.code" placeholder="VT-..." /></el-form-item></el-col>
          <el-col :span="14"><el-form-item label="Tên hàng hóa"><el-input v-model="form.name" /></el-form-item></el-col>
          <el-col :span="12"><el-form-item label="Nhóm hàng"><el-input v-model="form.category" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="Đơn vị"><el-input v-model="form.unit" /></el-form-item></el-col>
          <el-col :span="6"><el-form-item label="Tồn tối thiểu"><el-input-number v-model="form.minimum" :min="0" controls-position="right" /></el-form-item></el-col>
        </el-row>
      </el-form>
      <template #footer>
        <el-button @click="dialogOpen = false">Hủy</el-button>
        <el-button type="primary" :disabled="!form.code || !form.name" @click="addProduct">Lưu hàng hóa</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped lang="scss">
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;

  .el-input { width: 280px; }
  .el-select { width: 180px; }
}

.toolbar-spacer {
  flex: 1;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 10px;

  small {
    display: block;
    margin-top: 3px;
    color: var(--el-text-color-secondary);
  }
}

.product-image {
  display: grid;
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 8px;
  color: var(--el-color-primary);
  background: var(--el-color-primary-light-9);
}

.danger {
  color: var(--el-color-danger);
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 16px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

@media (max-width: 700px) {
  .toolbar .el-input,
  .toolbar .el-select {
    width: 100%;
  }

  .pagination span {
    display: none;
  }
}
</style>
