<script setup>
const stats = [
  { label: 'Tổng hàng hóa', value: '1.248', note: '+36 trong tháng', icon: 'Goods', color: '#409eff' },
  { label: 'Giá trị tồn kho', value: '2,84 tỷ', note: '+8,2% so tháng trước', icon: 'Money', color: '#67c23a' },
  { label: 'Sắp hết hàng', value: '18', note: 'Cần nhập bổ sung', icon: 'Warning', color: '#e6a23c' },
  { label: 'Phiếu chờ duyệt', value: '07', note: '3 nhập · 4 xuất', icon: 'Document', color: '#f56c6c' },
]

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
    <div class="welcome">
      <div>
        <p>Thứ Tư, 23 tháng 09</p>
        <h2>Chào buổi tối, Quản trị viên 👋</h2>
        <span>Tình hình kho hàng của bạn hôm nay.</span>
      </div>
      <el-button type="primary" :icon="'Plus'">Tạo phiếu mới</el-button>
    </div>

    <el-row :gutter="16" class="stats">
      <el-col v-for="item in stats" :key="item.label" :xs="24" :sm="12" :lg="6">
        <el-card shadow="hover" class="stat-card">
          <div class="stat-icon" :style="{ color: item.color, background: `${item.color}18` }">
            <el-icon :size="24"><component :is="item.icon" /></el-icon>
          </div>
          <div>
            <span>{{ item.label }}</span>
            <strong>{{ item.value }}</strong>
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
                <strong>Nhập xuất kho</strong>
                <small>Biến động 7 ngày gần nhất</small>
              </div>
              <el-radio-group size="small" model-value="week">
                <el-radio-button value="week">7 ngày</el-radio-button>
                <el-radio-button value="month">30 ngày</el-radio-button>
              </el-radio-group>
            </div>
          </template>
          <div class="chart">
            <div class="chart-grid">
              <span v-for="n in 5" :key="n" />
            </div>
            <div class="bars">
              <div v-for="(day, index) in ['T5', 'T6', 'T7', 'CN', 'T2', 'T3', 'T4']" :key="day" class="bar-group">
                <div class="bar-pair">
                  <i class="in" :style="{ height: `${45 + ((index * 17) % 48)}%` }" />
                  <i class="out" :style="{ height: `${30 + ((index * 11) % 55)}%` }" />
                </div>
                <span>{{ day }}</span>
              </div>
            </div>
          </div>
          <div class="legend"><i class="in" /> Nhập kho <i class="out" /> Xuất kho</div>
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

.stats {
  row-gap: 16px;
}

.stat-card :deep(.el-card__body) {
  display: flex;
  align-items: center;
  gap: 14px;
}

.stat-icon {
  display: grid;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 12px;
}

.stat-card span,
.stat-card small,
.card-title small {
  display: block;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.stat-card strong {
  display: block;
  margin: 3px 0;
  font-size: 24px;
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

.chart {
  position: relative;
  height: 210px;
}

.chart-grid {
  position: absolute;
  inset: 0 0 25px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;

  span {
    border-top: 1px dashed var(--el-border-color-lighter);
  }
}

.bars {
  position: absolute;
  inset: 10px 12px 0;
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
}

.bar-group {
  display: flex;
  height: 100%;
  flex-direction: column;
  justify-content: flex-end;
  gap: 7px;
  text-align: center;
  color: var(--el-text-color-secondary);
  font-size: 11px;
}

.bar-pair {
  display: flex;
  height: 175px;
  align-items: flex-end;
  gap: 4px;

  i {
    width: 12px;
    min-height: 8px;
    border-radius: 4px 4px 0 0;
  }
}

i.in { background: #409eff; }
i.out { background: #a0cfff; }

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
}
</style>
