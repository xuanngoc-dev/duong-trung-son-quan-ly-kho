<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import http from '@/api/http'
import {
  CustomButton,
  CustomCard,
  CustomCol,
  CustomDatePicker,
  CustomDialog,
  CustomForm,
  CustomFormItem,
  CustomIcon,
  CustomInput,
  CustomOption,
  CustomRow,
  CustomSelect,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import BulkActionBar from '@/components/BulkActionBar.vue'
import Pagination from '@/components/Pagination.vue'

const statuses = [
  { value: 'DA_KIEM', label: 'Chờ kiểm tra' },
  { value: 'DAT', label: 'Đạt' },
  { value: 'KHONG_DAT', label: 'Không đạt' },
  { value: 'DAC_CACH', label: 'Đặc cách nhận' },
  { value: 'TRA_HANG', label: 'Trả hàng' },
]

const emptyForm = () => ({
  nguyen_vat_lieu_id: null,
  so_lo_batch: '',
  ngay_nhap: '',
  so_luong: '',
  han_su_dung: '',
  trang_thai: 'DA_KIEM',
  ghi_chu: '',
})

const items = ref([])
const materials = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)
const loading = ref(false)
const resetPageOnReload = ref(false)
const saving = ref(false)
const bulkDeleting = ref(false)
const keyword = ref('')
const statusFilter = ref('')
const materialFilter = ref('')
const selectedRows = ref([])
const tableRef = ref()
const dialogOpen = ref(false)
const shouldReload = ref(false)
const formRef = ref()
const editingId = ref(null)
const form = reactive(emptyForm())
const statusUpdatingId = ref(null)

const selectedCount = computed(() => selectedRows.value.length)
const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value
      ? `Xóa ${selectedCount.value} lô đã chọn`
      : 'Chọn lô để xóa',
  },
])

const dialogTitle = computed(() => (editingId.value ? 'Sửa lô nguyên vật liệu' : 'Thêm lô nguyên vật liệu'))

const rules = {
  nguyen_vat_lieu_id: [{ required: true, message: 'Chọn nguyên vật liệu', trigger: 'change' }],
  so_lo_batch: [{ required: true, message: 'Nhập số lô', trigger: 'blur' }],
  ngay_nhap: [{ required: true, message: 'Chọn ngày nhập', trigger: 'change' }],
  so_luong: [
    {
      validator: (_rule, value, callback) => {
        if (value === '' || value == null) {
          callback(new Error('Nhập số lượng'))
          return
        }
        if (!/^\d+(\.\d{1,4})?$/.test(String(value).trim())) callback(new Error('Số lượng không hợp lệ'))
        else callback()
      },
      trigger: 'blur',
    },
  ],
  trang_thai: [{ required: true, message: 'Chọn trạng thái', trigger: 'change' }],
}

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || 'Không thể thực hiện thao tác.'
}

function materialLabel(item) {
  if (!item) return ''
  return `${item.ma_nguyen_vat_lieu} - ${item.ten_nguyen_vat_lieu}`
}

function statusLabel(value) {
  return statuses.find((item) => item.value === value)?.label || value || ''
}

function formatQuantity(value) {
  if (value == null || value === '') return ''
  const number = Number(value)
  if (Number.isNaN(number)) return value
  return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 4 }).format(number)
}

function formatDate(value) {
  if (!value) return ''
  const [year, month, day] = String(value).slice(0, 10).split('-')
  if (!year || !month || !day) return value
  return `${day}/${month}/${year}`
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function searchItems() {
  page.value = 1
  loadItems()
}

async function loadMaterials() {
  const { data } = await http.get('/danh-muc-nguyen-vat-lieu', { params: { limit: 100 } })
  materials.value = data.data || []
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  tableRef.value?.clearSelection?.()
  try {
    const { data } = await http.get('/lo-nguyen-vat-lieu', {
      params: {
        q: keyword.value.trim() || undefined,
        trang_thai: statusFilter.value || undefined,
        nguyen_vat_lieu_id: materialFilter.value || undefined,
        start: (page.value - 1) * limit.value,
        limit: limit.value,
      },
    })
    total.value = Number(data.total) || 0
    const lastPage = Math.max(1, Math.ceil(total.value / limit.value) || 1)
    if (page.value > lastPage) {
      page.value = lastPage
      return loadItems()
    }
    items.value = data.data
  } catch (error) {
    ElMessage.error(errorMessage(error))
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function onDialogClosed() {
  resetForm()
  if (!shouldReload.value) return
  shouldReload.value = false
  if (resetPageOnReload.value) {
    resetPageOnReload.value = false
    page.value = 1
  }
  loadItems()
}

function openCreate() {
  resetForm()
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, emptyForm(), {
    nguyen_vat_lieu_id: row.nguyen_vat_lieu_id || null,
    so_lo_batch: row.so_lo_batch || '',
    ngay_nhap: row.ngay_nhap || '',
    so_luong: row.so_luong == null ? '' : String(Number(row.so_luong)),
    han_su_dung: row.han_su_dung || '',
    trang_thai: row.trang_thai || 'DA_KIEM',
    ghi_chu: row.ghi_chu || '',
  })
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function payloadFrom(source) {
  return {
    nguyen_vat_lieu_id: source.nguyen_vat_lieu_id,
    so_lo_batch: String(source.so_lo_batch).trim(),
    ngay_nhap: source.ngay_nhap,
    so_luong: String(source.so_luong).trim(),
    han_su_dung: source.han_su_dung || null,
    trang_thai: source.trang_thai,
    ghi_chu: String(source.ghi_chu || '').trim(),
  }
}

async function saveItem() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    const { data } = editingId.value
      ? await http.put(`/lo-nguyen-vat-lieu/${editingId.value}`, payloadFrom(form))
      : await http.post('/lo-nguyen-vat-lieu', payloadFrom(form))
    ElMessage.success(data.message)
    resetPageOnReload.value = !editingId.value
    shouldReload.value = true
    dialogOpen.value = false
  } catch (error) {
    ElMessage.error(errorMessage(error))
  } finally {
    saving.value = false
  }
}

async function removeItems(rows) {
  const targets = [...rows]
  if (!targets.length) return
  const label = targets.length === 1 ? `lô ${targets[0].so_lo_batch}` : `${targets.length} lô đã chọn`
  try {
    await ElMessageBox.confirm(`Xóa ${label}?`, 'Xóa lô nguyên vật liệu', {
      type: 'warning',
      confirmButtonText: 'Xóa',
      cancelButtonText: 'Hủy',
    })
  } catch {
    return
  }

  bulkDeleting.value = true
  try {
    for (const row of targets) {
      await http.delete(`/lo-nguyen-vat-lieu/${row.id}`)
    }
    ElMessage.success(targets.length === 1 ? 'Đã xóa lô nguyên vật liệu.' : `Đã xóa ${targets.length} lô.`)
    await loadItems()
  } catch (error) {
    ElMessage.error(errorMessage(error))
    await loadItems()
  } finally {
    bulkDeleting.value = false
  }
}

function onBulkAction(key) {
  if (key === 'delete') removeItems(selectedRows.value)
}

async function updateStatus(row, value) {
  if (!row?.id || statusUpdatingId.value === row.id || row.trang_thai === value) return
  const previous = row.trang_thai
  row.trang_thai = value
  statusUpdatingId.value = row.id
  try {
    const { data } = await http.put(`/lo-nguyen-vat-lieu/${row.id}`, {
      nguyen_vat_lieu_id: row.nguyen_vat_lieu_id,
      so_lo_batch: row.so_lo_batch,
      ngay_nhap: row.ngay_nhap,
      so_luong: row.so_luong,
      han_su_dung: row.han_su_dung,
      trang_thai: value,
      ghi_chu: row.ghi_chu || '',
    })
    ElMessage.success(data.message || 'Đã cập nhật trạng thái.')
  } catch (error) {
    row.trang_thai = previous
    ElMessage.error(errorMessage(error))
  } finally {
    statusUpdatingId.value = null
  }
}

onMounted(() => {
  loadMaterials().catch((error) => ElMessage.error(errorMessage(error)))
  loadItems()
})
</script>

<template>
  <div class="page-list">
    <CustomCard shadow="never" class="filter-card">
      <div class="filters">
        <CustomInput
          v-model="keyword"
          clearable
          placeholder="Tìm theo số lô, mã hoặc tên NVL..."
          @keyup.enter="searchItems"
          @clear="searchItems"
        >
          <template #prefix><CustomIcon><Search /></CustomIcon></template>
        </CustomInput>
        <CustomSelect v-model="materialFilter" clearable filterable placeholder="Tất cả nguyên vật liệu">
          <CustomOption
            v-for="item in materials"
            :key="item.id"
            :label="materialLabel(item)"
            :value="item.id"
          />
        </CustomSelect>
        <CustomSelect v-model="statusFilter" clearable placeholder="Tất cả trạng thái">
          <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
        </CustomSelect>
        <CustomButton type="primary" @click="searchItems">Tìm kiếm</CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="never" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách lô nguyên vật liệu</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm
            </CustomButton>
          </BulkActionBar>
        </div>
      </template>

      <CustomTable
        ref="tableRef"
        v-loading="loading"
        :data="items"
        row-key="id"
        stripe
        @selection-change="onSelectionChange"
      >
        <CustomTableColumn type="selection" width="48" align="center" />
        <CustomTableColumn label="STT" width="70" align="center">
          <template #default="{ $index }">{{ (page - 1) * limit + $index + 1 }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Mã NVL" width="130" show-overflow-tooltip>
          <template #default="{ row }">{{ row.nguyen_vat_lieu?.ma_nguyen_vat_lieu }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Tên nguyên vật liệu" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">{{ row.nguyen_vat_lieu?.ten_nguyen_vat_lieu }}</template>
        </CustomTableColumn>
        <CustomTableColumn prop="so_lo_batch" label="Số lô" min-width="140" show-overflow-tooltip />
        <CustomTableColumn label="Ngày nhập" width="120">
          <template #default="{ row }">{{ formatDate(row.ngay_nhap) }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Số lượng" width="130" align="right">
          <template #default="{ row }">
            {{ formatQuantity(row.so_luong) }} {{ row.nguyen_vat_lieu?.don_vi_tinh }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Hạn sử dụng" width="130">
          <template #default="{ row }">{{ formatDate(row.han_su_dung) }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="180">
          <template #default="{ row }">
            <CustomSelect
              v-if="row.id"
              :model-value="row.trang_thai"
              class="status-select"
              :clearable="false"
              :disabled="statusUpdatingId === row.id"
              @change="(value) => updateStatus(row, value)"
            >
              <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
            </CustomSelect>
            <span v-else>{{ statusLabel(row.trang_thai) }}</span>
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="160" show-overflow-tooltip />
        <CustomTableColumn label="Thao tác" width="110" align="center" fixed="right">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sửa" placement="top">
                <CustomButton text circle @click="openEdit(row)"><CustomIcon><Edit /></CustomIcon></CustomButton>
              </CustomTooltip>
              <CustomTooltip content="Xóa" placement="top">
                <CustomButton text circle type="danger" @click="removeItems([row])"><CustomIcon><Delete /></CustomIcon></CustomButton>
              </CustomTooltip>
            </div>
          </template>
        </CustomTableColumn>
      </CustomTable>

      <Pagination
        v-model="page"
        v-model:page-size="limit"
        :total="total"
        :disabled="loading"
        @change="loadItems"
      />
    </CustomCard>

    <CustomDialog v-model="dialogOpen" :title="dialogTitle" :width="1080" @closed="onDialogClosed">
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="14">
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Nguyên vật liệu" prop="nguyen_vat_lieu_id">
              <CustomSelect v-model="form.nguyen_vat_lieu_id" class="full-control" filterable placeholder="Chọn nguyên vật liệu">
                <CustomOption
                  v-for="item in materials"
                  :key="item.id"
                  :label="materialLabel(item)"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Số lô" prop="so_lo_batch">
              <CustomInput v-model="form.so_lo_batch" placeholder="LO-2026-001" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Ngày nhập" prop="ngay_nhap">
              <CustomDatePicker v-model="form.ngay_nhap" class="full-control" type="date" value-format="YYYY-MM-DD" placeholder="Chọn ngày" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Số lượng" prop="so_luong">
              <CustomInput v-model="form.so_luong" placeholder="0" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Hạn sử dụng" prop="han_su_dung">
              <CustomDatePicker v-model="form.han_su_dung" class="full-control" type="date" value-format="YYYY-MM-DD" placeholder="Chọn ngày" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" class="full-control" :clearable="false">
                <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="12">
            <CustomFormItem label="Ghi chú" prop="ghi_chu">
              <CustomInput v-model="form.ghi_chu" placeholder="Ghi chú thêm" />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogOpen = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="saveItem">Lưu</CustomButton>
      </template>
    </CustomDialog>
  </div>
</template>

<style scoped lang="scss">
.filter-card :deep(.el-card__body) {
  padding-bottom: 16px;
}

.filters {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 10px;
  min-width: 0;

  :deep(.el-input) { width: 280px; }
  :deep(.el-select) { width: 220px; }
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.card-title {
  min-width: 0;
  font-weight: 600;
}

.action-btns {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.full-control,
.status-select {
  width: 100%;
}

@media (max-width: 767px) {
  .filters {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 6px;

    :deep(.el-input),
    :deep(.el-select) {
      width: 100%;
      min-width: 0;
    }
  }

  .card-header :deep(.bulk-action-bar) {
    flex-wrap: nowrap;
    gap: 6px;
  }

  .card-header :deep(.el-button) {
    padding-inline: 8px;
  }
}
</style>
