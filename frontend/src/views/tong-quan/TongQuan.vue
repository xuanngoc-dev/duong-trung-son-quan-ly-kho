<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import http from '@/api/http'
import { CustomButton, CustomDatePicker } from '@/components/element'

const presets = [
  { key: 'today', label: 'Hôm nay' },
  // { key: 'yesterday', label: 'Hôm qua' },
  { key: 'week', label: 'Tuần này' },
  { key: 'month', label: 'Tháng này' },
  // { key: 'lastMonth', label: 'Tháng trước' },
  // { key: 'quarter', label: 'Quý này' },
  // { key: 'lastQuarter', label: 'Quý trước' },
  { key: 'year', label: 'Năm nay' },
]

function startOfDay(date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate())
}

function formatIso(date) {
  const pad = (value) => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function formatDisplay(value) {
  const [year, month, day] = String(value || '').split('-')
  if (!year || !month || !day) return ''
  return `${day}/${month}/${year}`
}

function rangeFor(key) {
  const today = startOfDay(new Date())
  if (key === 'today') return [today, today]
  if (key === 'yesterday') {
    const day = new Date(today)
    day.setDate(day.getDate() - 1)
    return [day, day]
  }
  if (key === 'week') {
    const start = new Date(today)
    const weekday = start.getDay() || 7
    start.setDate(start.getDate() - weekday + 1)
    return [start, today]
  }
  if (key === 'month') return [new Date(today.getFullYear(), today.getMonth(), 1), today]
  if (key === 'lastMonth') {
    return [
      new Date(today.getFullYear(), today.getMonth() - 1, 1),
      new Date(today.getFullYear(), today.getMonth(), 0),
    ]
  }
  if (key === 'quarter') {
    const quarter = Math.floor(today.getMonth() / 3)
    return [new Date(today.getFullYear(), quarter * 3, 1), today]
  }
  if (key === 'lastQuarter') {
    const quarter = Math.floor(today.getMonth() / 3) - 1
    const year = quarter < 0 ? today.getFullYear() - 1 : today.getFullYear()
    const startMonth = ((quarter + 4) % 4) * 3
    return [new Date(year, startMonth, 1), new Date(year, startMonth + 3, 0)]
  }
  return [new Date(today.getFullYear(), 0, 1), today]
}

const activePreset = ref('month')
const dateRange = ref(rangeFor('month').map(formatIso))

const periodLabel = computed(() => {
  const [from, to] = dateRange.value || []
  if (!from || !to) return 'Chọn khoảng thời gian'
  return `${formatDisplay(from)} - ${formatDisplay(to)}`
})

function applyPreset(key) {
  activePreset.value = key
  dateRange.value = rangeFor(key).map(formatIso)
}

function onRangeChange(value) {
  const [from, to] = value || []
  activePreset.value = presets.find((item) => {
    const [start, end] = rangeFor(item.key).map(formatIso)
    return start === from && end === to
  })?.key || ''
}

const overview = ref(null)
const overviewLoading = ref(false)

const stats = computed(() => {
  const data = overview.value
  const tong = data?.tong ?? 0
  const ok = data?.ok ?? 0
  const ng = data?.ng ?? 0
  const itLoi = data?.it_loi
  const nhieuLoi = data?.nhieu_loi

  return [
    { label: 'Số phiếu kiểm tra', value: String(tong), note: `OK ${ok} · NG ${ng}`, icon: 'Document', color: '#409eff' },
    { label: 'Phiếu OK', value: String(ok), note: tong ? `${data.ty_le_ok}% phiếu đạt` : 'Chưa có phiếu', icon: 'CircleCheck', color: '#67c23a' },
    { label: 'Phiếu NG', value: String(ng), note: tong ? `${data.ty_le_ng}% phiếu không đạt` : 'Chưa có phiếu', icon: 'CircleClose', color: '#f56c6c' },
    { label: 'Tỷ lệ đạt', value: tong ? `${data.ty_le_ok}%` : '0%', note: `${ok}/${tong} phiếu OK`, icon: 'DataAnalysis', color: '#13c2c2' },
    {
      label: 'Ít lỗi nhất',
      value: itLoi?.ten || '—',
      note: itLoi ? `${itLoi.ng} NG / ${itLoi.tong} phiếu` : 'Chưa có dữ liệu',
      icon: 'Medal',
      color: '#67c23a',
    },
    {
      label: 'Nhiều lỗi nhất',
      value: nhieuLoi?.ten || '—',
      note: nhieuLoi ? `${nhieuLoi.ng} NG / ${nhieuLoi.tong} phiếu` : 'Chưa có dữ liệu',
      icon: 'Warning',
      color: '#e6a23c',
    },
  ]
})

const supplierCategories = computed(() => (overview.value?.nha_cung_cap || []).map((item) => item.ten))
const supplierMax = computed(() => {
  const rows = overview.value?.nha_cung_cap || []
  return Math.max(1, ...rows.flatMap((item) => [item.ok, item.ng]))
})

const yTicks = computed(() => {
  const max = supplierMax.value
  if (max <= 6) return Array.from({ length: max + 1 }, (_, index) => max - index)
  const steps = 4
  const ticks = Array.from({ length: steps + 1 }, (_, index) => Math.round((max * (steps - index)) / steps))
  return [...new Set(ticks)]
})

function barHeight(value) {
  if (!value) return '0%'
  return `${Math.max(6, (value / supplierMax.value) * 86)}%`
}

async function loadOverview() {
  const [from, to] = dateRange.value || []
  overviewLoading.value = true
  try {
    const { data } = await http.get('/tong-quan', {
      params: { tu_ngay: from || undefined, den_ngay: to || undefined },
    })
    overview.value = data.data
  } catch (error) {
    overview.value = null
    ElMessage.error(error.response?.data?.message || 'Không tải được số liệu tổng quan.')
  } finally {
    overviewLoading.value = false
  }
}

watch(dateRange, loadOverview)
onMounted(loadOverview)

const lowStock = [
  { code: 'VT-00124', name: 'Ống PVC Bình Minh Ø90', warehouse: 'Kho A', stock: 8, minimum: 20 },
  { code: 'VT-00318', name: 'Co nối ren trong 27', warehouse: 'Kho A', stock: 12, minimum: 30 },
  { code: 'VT-00542', name: 'Van bi đồng D34', warehouse: 'Kho B', stock: 4, minimum: 15 },
  { code: 'VT-00611', name: 'Băng tan PTFE 12mm', warehouse: 'Kho B', stock: 24, minimum: 50 },
]

const activities = [
  { title: 'Nhập kho NK-20260923-08', detail: '52 mặt hàng · Nguyễn Văn Minh', time: '10 phút trước', type: 'success' },
  { title: 'Xuất kho XK-20260923-12', detail: 'Công trình Nguyễn Trãi', time: '32 phút trước', type: 'primary' },
  { title: 'Điều chỉnh tồn kho', detail: 'Van bi đồng D34 · -2 sản phẩm', time: '1 giờ trước', type: 'warning' },
]
</script>

<template>
  <div class="dashboard">
    <div class="period-filter">
      <div class="period-range">
        <CustomDatePicker
          v-model="dateRange"
          size="small"
          type="daterange"
          value-format="YYYY-MM-DD"
          range-separator="-"
          start-placeholder="Từ ngày"
          end-placeholder="Đến ngày"
          @change="onRangeChange"
        />
      </div>
      <div class="period-shortcuts">
        <CustomButton
          v-for="item in presets"
          :key="item.key"
          size="small"
          :type="activePreset === item.key ? 'primary' : 'default'"
          @click="applyPreset(item.key)"
        >
          {{ item.label }}
        </CustomButton>
      </div>
      <span class="period-label">{{ periodLabel }}</span>
    </div>

    <el-row v-loading="overviewLoading" :gutter="16" class="stats">
      <el-col v-for="item in stats" :key="item.label" :xs="12" :sm="12" :lg="4">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-icon" :style="{ color: item.color, background: `${item.color}18` }">
            <el-icon :size="18"><component :is="item.icon" /></el-icon>
          </div>
            <div class="stat-copy">
              <span>{{ item.label }}</span>
              <strong :title="item.value">{{ item.value }}</strong>
              <small>{{ item.note }}</small>
            </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="16">
      <el-col :xs="24" :lg="16">
        <el-card shadow="never" class="chart-card">
          <template #header>
            <div class="card-title">
              <div>
                <strong>Tỷ lệ OK / NG theo nhà cung cấp</strong>
                <small>{{ periodLabel }}</small>
              </div>
            </div>
          </template>
          <div v-if="supplierCategories.length" class="chart-plot">
            <div class="y-axis">
              <span v-for="(tick, index) in yTicks" :key="`${tick}-${index}`">{{ tick }}</span>
            </div>
            <div class="chart">
              <div class="bars">
                <div v-for="item in overview.nha_cung_cap" :key="item.ma || item.ten" class="bar-group" :title="`${item.ten}: OK ${item.ok} · NG ${item.ng} · lỗi ${item.ty_le_ng}%`">
                  <div class="bar-pair">
                    <i class="in" :style="{ height: barHeight(item.ok) }"><b>{{ item.ok }}</b></i>
                    <i class="out" :style="{ height: barHeight(item.ng) }"><b>{{ item.ng }}</b></i>
                  </div>
                  <span>{{ item.ma || item.ten }}</span>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="chart-empty">Không có phiếu kiểm tra trong khoảng thời gian này.</p>
          <div class="legend"><i class="in" /> OK <i class="out" /> NG</div>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="8">
        <el-card shadow="never" class="activity-card">
          <template #header><strong>Hoạt động gần đây</strong></template>
          <el-timeline>
            <el-timeline-item
              v-for="item in activities"
              :key="item.title"
              :type="item.type"
              :timestamp="item.time"
            >
              <strong>{{ item.title }}</strong>
              <p>{{ item.detail }}</p>
            </el-timeline-item>
          </el-timeline>
          <el-button text type="primary">Xem tất cả hoạt động →</el-button>
        </el-card>
      </el-col>
    </el-row>

    <el-card shadow="never" class="stock-card">
      <template #header>
        <div class="card-title">
          <div>
            <strong>Hàng sắp hết</strong>
            <small>Các mặt hàng dưới định mức tối thiểu</small>
          </div>
          <el-button text type="primary">Xem toàn bộ</el-button>
        </div>
      </template>
      <el-table :data="lowStock">
        <el-table-column prop="code" label="Mã hàng" width="140" />
        <el-table-column prop="name" label="Tên hàng hóa" min-width="220" />
        <el-table-column prop="warehouse" label="Kho" width="120" />
        <el-table-column label="Tồn hiện tại" width="140">
          <template #default="{ row }">
            <el-tag type="danger" effect="light">{{ row.stock }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="minimum" label="Định mức" width="120" />
        <el-table-column align="right" width="130">
          <template #default><el-button size="small" type="primary" plain>Nhập hàng</el-button></template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<style scoped lang="scss">
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.welcome {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;

  p, span {
    margin: 0;
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  h2 {
    margin: 5px 0;
    font-size: 24px;
  }
}

.period-filter {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.period-range {
  flex: none;
  width: 210px;

  :deep(.el-date-editor) {
    width: 100%;
  }
}

.period-shortcuts {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.period-label {
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.stats {
  row-gap: 16px;
}

.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px;
}

.stat-icon {
  display: grid;
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 10px;
}

.stat-card span,
.stat-card small,
.card-title small {
  display: block;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.stat-copy {
  min-width: 0;
}

.stat-copy strong,
.stat-copy small {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stat-card strong {
  display: block;
  margin: 2px 0;
  font-size: 18px;
}

.card-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  small {
    margin-top: 4px;
  }
}

.chart-card,
.activity-card {
  height: 350px;
}

.chart-plot {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.y-axis {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  flex: none;
  width: 28px;
  height: 175px;
  color: var(--el-text-color-secondary);
  font-size: 11px;
  line-height: 1;
  text-align: right;
}

.chart {
  flex: 1;
  min-width: 0;
  height: 230px;
  overflow-x: auto;
}

.bars {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  height: 100%;
  min-width: 100%;
  width: max-content;
  padding: 0 4px;
}

.bar-group {
  display: flex;
  width: 56px;
  height: 100%;
  flex: none;
  flex-direction: column;
  justify-content: flex-end;
  gap: 7px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 11px;

  span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.bar-pair {
  display: flex;
  height: 175px;
  align-items: flex-end;
  justify-content: center;
  gap: 4px;
  padding-top: 16px;
  box-sizing: border-box;

  i {
    position: relative;
    width: 16px;
    min-height: 0;
    border-radius: 4px 4px 0 0;
  }

  b {
    position: absolute;
    top: -14px;
    left: 50%;
    color: var(--el-text-color-primary);
    font-size: 10px;
    font-style: normal;
    font-weight: 600;
    line-height: 1;
    transform: translateX(-50%);
  }
}

i.in { background: #67c23a; }
i.out { background: #f56c6c; }

.chart-empty {
  margin: 48px 0;
  color: var(--el-text-color-secondary);
  text-align: center;
}

.legend {
  display: flex;
  justify-content: center;
  gap: 8px;
  color: var(--el-text-color-secondary);
  font-size: 12px;

  i {
    width: 10px;
    height: 10px;
    margin-left: 10px;
    border-radius: 3px;
  }
}

.activity-card {
  :deep(.el-timeline) {
    padding-left: 6px;
  }

  p {
    margin: 4px 0 0;
    color: var(--el-text-color-secondary);
    font-size: 12px;
  }
}

@media (max-width: 1199px) {
  .activity-card {
    height: auto;
    margin-top: 16px;
  }
}

@media (max-width: 600px) {
  .welcome {
    align-items: flex-start;
    flex-direction: column;
  }

  .chart-card {
    height: 330px;
  }

  .period-range,
  .period-range :deep(.el-date-editor) {
    width: 100%;
  }
}
</style>
