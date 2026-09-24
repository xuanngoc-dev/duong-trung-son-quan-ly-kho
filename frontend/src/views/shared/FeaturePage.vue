<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const keyword = ref('')
const title = computed(() => route.meta.title)
const subtitle = computed(() => route.meta.subtitle)
const action = computed(() => route.meta.action)
const type = computed(() => route.meta.type)

const rows = computed(() => {
  const prefixes = {
    receipt: 'NK',
    issue: 'XK',
    stocktake: 'KK',
    inventory: 'VT',
    warehouse: 'KHO',
    supplier: 'NCC',
    report: 'BC',
    settings: 'CH',
  }
  const prefix = prefixes[type.value] || 'DM'

  return Array.from({ length: 6 }, (_, index) => ({
    code: `${prefix}-20260923-${String(index + 1).padStart(2, '0')}`,
    name:
      type.value === 'supplier'
        ? ['Công ty Bình Minh', 'Nhựa Tiền Phong', 'Thiết bị Minh Hòa'][index % 3]
        : type.value === 'warehouse'
          ? ['Kho vật tư A', 'Kho thiết bị B', 'Kho công trình'][index % 3]
          : ['Ống PVC & phụ kiện', 'Thiết bị cấp nước', 'Vật tư công trình'][index % 3],
    warehouse: ['Kho A', 'Kho B', 'Kho trung tâm'][index % 3],
    quantity: 18 + index * 13,
    date: `${23 - index}/09/2026`,
    status: ['Hoàn thành', 'Chờ duyệt', 'Đang xử lý'][index % 3],
  }))
})

const filteredRows = computed(() => {
  const value = keyword.value.trim().toLowerCase()
  return value
    ? rows.value.filter((row) => `${row.code} ${row.name}`.toLowerCase().includes(value))
    : rows.value
})

const statusType = (status) =>
  ({ 'Hoàn thành': 'success', 'Chờ duyệt': 'warning', 'Đang xử lý': 'primary' })[status]
</script>

<template>
  <div class="page-list">
    <div class="page-heading">
      <div>
        <h2>{{ title }}</h2>
        <p>{{ subtitle }}</p>
      </div>
      <el-button type="primary">
        <el-icon><Plus /></el-icon>
        {{ action }}
      </el-button>
    </div>

    <el-row :gutter="14" class="summary">
      <el-col v-for="item in [
        { label: 'Tổng số', value: 128, icon: 'Files', color: '#409eff' },
        { label: 'Trong tháng', value: 42, icon: 'Calendar', color: '#67c23a' },
        { label: 'Chờ xử lý', value: 7, icon: 'Clock', color: '#e6a23c' },
      ]" :key="item.label" :xs="24" :sm="8">
        <el-card shadow="never">
          <div class="summary-item">
            <el-icon :size="24" :style="{ color: item.color }"><component :is="item.icon" /></el-icon>
            <div><span>{{ item.label }}</span><strong>{{ item.value }}</strong></div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never">
      <div class="toolbar">
        <el-input v-model="keyword" clearable placeholder="Tìm kiếm mã hoặc nội dung...">
          <template #prefix><el-icon><Search /></el-icon></template>
        </el-input>
        <el-date-picker type="daterange" start-placeholder="Từ ngày" end-placeholder="Đến ngày" />
        <el-select placeholder="Tất cả trạng thái" clearable>
          <el-option label="Hoàn thành" value="done" />
          <el-option label="Chờ duyệt" value="pending" />
        </el-select>
        <div class="spacer" />
        <el-button><el-icon><Download /></el-icon>Xuất dữ liệu</el-button>
      </div>

      <el-table :data="filteredRows" stripe>
        <el-table-column prop="code" label="Mã chứng từ" min-width="170" />
        <el-table-column prop="name" label="Nội dung" min-width="220" />
        <el-table-column prop="warehouse" label="Kho" width="150" />
        <el-table-column prop="quantity" label="Số lượng" width="110" align="right" />
        <el-table-column prop="date" label="Ngày tạo" width="130" />
        <el-table-column label="Trạng thái" width="130">
          <template #default="{ row }">
            <el-tag :type="statusType(row.status)" effect="light">{{ row.status }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column width="90" align="right">
          <template #default>
            <el-button text circle><el-icon><View /></el-icon></el-button>
            <el-button text circle><el-icon><MoreFilled /></el-icon></el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <span>Hiển thị 1–6 trong 128 bản ghi</span>
        <el-pagination layout="prev, pager, next" :total="128" :page-size="10" />
      </div>
    </el-card>
  </div>
</template>

<style scoped lang="scss">
.summary {
  margin-bottom: 14px;
  row-gap: 14px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 14px;

  span {
    display: block;
    color: var(--el-text-color-secondary);
    font-size: 12px;
  }

  strong {
    font-size: 24px;
  }
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;

  .el-input { width: 280px; }
  .el-select { width: 180px; }
}

.spacer {
  flex: 1;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 16px;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}

@media (max-width: 700px) {
  .toolbar .el-input,
  .toolbar .el-select,
  .toolbar :deep(.el-date-editor) {
    width: 100%;
  }

  .pagination span {
    display: none;
  }
}
</style>
