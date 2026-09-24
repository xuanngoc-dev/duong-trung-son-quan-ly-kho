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
    <el-card>  
      <div class="card-title">
        <h2>Comming soon...</h2>
        <small>Chức năng đang phát triển...</small>
      </div>
      <div class="card-content">
        <slot />
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
