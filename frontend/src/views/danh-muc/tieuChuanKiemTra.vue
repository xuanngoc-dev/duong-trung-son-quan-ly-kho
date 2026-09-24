<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import http from '@/api/http'
import {
  CustomButton,
  CustomCard,
  CustomCol,
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
  { value: 'Active', label: 'Đang dùng' },
  { value: 'Inactive', label: 'Ngừng dùng' },
  { value: 'Draft', label: 'Nháp' },
]

const kinds = [
  { value: 'NUMERIC', label: 'Số' },
  { value: 'TEXT', label: 'Văn bản' },
]

const emptyDetail = () => ({
  hang_muc_kiem_tra: '',
  loai_tieu_chuan: 'NUMERIC',
  gia_tri_dinh_muc: '',
  dung_sai_tren: '',
  dung_sai_duoi: '',
  tieu_chuan_mo_ta: '',
  don_vi_tinh: '',
  phuong_phap_kiem_tra: '',
  dung_cu_thiet_bi: '',
})

const emptyForm = () => ({
  ma_san_pham: '',
  ten_san_pham: '',
  phien_ban: 'v1.0',
  trang_thai: 'Active',
  chi_tiet: [],
})

const items = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)
const loading = ref(false)
const resetPageOnReload = ref(false)
const saving = ref(false)
const bulkDeleting = ref(false)
const keyword = ref('')
const statusFilter = ref('')
const selectedRows = ref([])
const tableRef = ref()
const dialogOpen = ref(false)
const shouldReload = ref(false)
const formRef = ref()
const editingId = ref(null)
const form = reactive(emptyForm())
const statusUpdatingId = ref(null)
const detailError = ref('')

const selectedCount = computed(() => selectedRows.value.length)
const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value ? `Xóa ${selectedCount.value} tiêu chuẩn đã chọn` : 'Chọn tiêu chuẩn để xóa',
  },
])
const dialogTitle = computed(() => (editingId.value ? 'Sửa tiêu chuẩn kiểm tra' : 'Thêm tiêu chuẩn kiểm tra'))

const rules = {
  ma_san_pham: [{ required: true, message: 'Nhập mã sản phẩm', trigger: 'blur' }],
  ten_san_pham: [{ required: true, message: 'Nhập tên sản phẩm', trigger: 'blur' }],
  trang_thai: [{ required: true, message: 'Chọn trạng thái', trigger: 'change' }],
}

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || 'Không thể thực hiện thao tác.'
}

function statusLabel(value) {
  return statuses.find((item) => item.value === value)?.label || value || ''
}

function decimalText(value) {
  if (value == null || value === '') return ''
  const number = Number(value)
  if (Number.isNaN(number)) return String(value)
  return String(number)
}

function validDecimal(value) {
  return value === '' || value == null || /^-?\d+(\.\d{1,4})?$/.test(String(value).trim())
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function searchItems() {
  page.value = 1
  loadItems()
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  tableRef.value?.clearSelection?.()
  try {
    const { data } = await http.get('/danh-muc-tieu-chuan', {
      params: {
        q: keyword.value.trim() || undefined,
        trang_thai: statusFilter.value || undefined,
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

function mapDetails(rows) {
  return (rows || []).map((item) => ({
    hang_muc_kiem_tra: item.hang_muc_kiem_tra || '',
    loai_tieu_chuan: item.loai_tieu_chuan || 'NUMERIC',
    gia_tri_dinh_muc: decimalText(item.gia_tri_dinh_muc),
    dung_sai_tren: decimalText(item.dung_sai_tren),
    dung_sai_duoi: decimalText(item.dung_sai_duoi),
    tieu_chuan_mo_ta: item.tieu_chuan_mo_ta || '',
    don_vi_tinh: item.don_vi_tinh || '',
    phuong_phap_kiem_tra: item.phuong_phap_kiem_tra || '',
    dung_cu_thiet_bi: item.dung_cu_thiet_bi || '',
  }))
}

function resetForm() {
  editingId.value = null
  detailError.value = ''
  Object.assign(form, emptyForm(), { chi_tiet: [] })
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
  detailError.value = ''
  Object.assign(form, emptyForm(), {
    ma_san_pham: row.ma_san_pham || '',
    ten_san_pham: row.ten_san_pham || '',
    phien_ban: row.phien_ban || 'v1.0',
    trang_thai: row.trang_thai || 'Active',
    chi_tiet: mapDetails(row.chi_tiet),
  })
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function addDetail() {
  form.chi_tiet.push(emptyDetail())
  detailError.value = ''
}

function removeDetail(index) {
  form.chi_tiet.splice(index, 1)
}

function collectedDetails() {
  const rows = []
  for (const item of form.chi_tiet) {
    const hangMuc = item.hang_muc_kiem_tra.trim()
    if (!hangMuc) return null
    if (![item.gia_tri_dinh_muc, item.dung_sai_tren, item.dung_sai_duoi].every(validDecimal)) return 'decimal'
    rows.push({
      hang_muc_kiem_tra: hangMuc,
      loai_tieu_chuan: item.loai_tieu_chuan || 'NUMERIC',
      gia_tri_dinh_muc: String(item.gia_tri_dinh_muc ?? '').trim(),
      dung_sai_tren: String(item.dung_sai_tren ?? '').trim(),
      dung_sai_duoi: String(item.dung_sai_duoi ?? '').trim(),
      tieu_chuan_mo_ta: item.tieu_chuan_mo_ta.trim(),
      don_vi_tinh: item.don_vi_tinh.trim(),
      phuong_phap_kiem_tra: item.phuong_phap_kiem_tra.trim(),
      dung_cu_thiet_bi: item.dung_cu_thiet_bi.trim(),
    })
  }
  return rows
}

function payloadFrom(source, details) {
  return {
    ma_san_pham: String(source.ma_san_pham).trim(),
    ten_san_pham: String(source.ten_san_pham).trim(),
    phien_ban: String(source.phien_ban || '').trim() || 'v1.0',
    trang_thai: source.trang_thai,
    chi_tiet: details,
  }
}

async function saveItem() {
  const valid = await formRef.value?.validate().catch(() => false)
  const details = collectedDetails()
  if (details === 'decimal') {
    detailError.value = 'Giá trị số tối đa 4 chữ số thập phân.'
  } else {
    detailError.value = details ? '' : 'Nhập hạng mục kiểm tra.'
  }
  if (!valid || !Array.isArray(details)) return

  saving.value = true
  try {
    const { data } = editingId.value
      ? await http.put(`/danh-muc-tieu-chuan/${editingId.value}`, payloadFrom(form, details))
      : await http.post('/danh-muc-tieu-chuan', payloadFrom(form, details))
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
  const label = targets.length === 1 ? `tiêu chuẩn ${targets[0].ma_san_pham}` : `${targets.length} tiêu chuẩn đã chọn`
  try {
    await ElMessageBox.confirm(`Xóa ${label}?`, 'Xóa tiêu chuẩn kiểm tra', {
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
      await http.delete(`/danh-muc-tieu-chuan/${row.id}`)
    }
    ElMessage.success(targets.length === 1 ? 'Đã xóa tiêu chuẩn kiểm tra.' : `Đã xóa ${targets.length} tiêu chuẩn.`)
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
    const { data } = await http.put(`/danh-muc-tieu-chuan/${row.id}`, payloadFrom(row, mapDetails(row.chi_tiet)))
    ElMessage.success(data.message || 'Đã cập nhật trạng thái.')
  } catch (error) {
    row.trang_thai = previous
    ElMessage.error(errorMessage(error))
  } finally {
    statusUpdatingId.value = null
  }
}

onMounted(loadItems)
</script>

<template>
  <div class="page-list">
    <CustomCard shadow="never" class="filter-card">
      <div class="filters">
        <CustomInput
          v-model="keyword"
          clearable
          placeholder="Tìm theo mã, tên sản phẩm, phiên bản..."
          @keyup.enter="searchItems"
          @clear="searchItems"
        >
          <template #prefix><CustomIcon><Search /></CustomIcon></template>
        </CustomInput>
        <CustomSelect v-model="statusFilter" clearable placeholder="Tất cả trạng thái" @change="searchItems" @clear="searchItems">
          <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
        </CustomSelect>
        <CustomButton type="primary" @click="searchItems">Tìm kiếm</CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="never" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Tiêu chuẩn kiểm tra</span>
          <BulkActionBar :actions="bulkActions" @action="onBulkAction">
            <CustomButton type="primary" @click="openCreate">
              <CustomIcon><Plus /></CustomIcon>
              Thêm
            </CustomButton>
          </BulkActionBar>
        </div>
      </template>

      <CustomTable ref="tableRef" v-loading="loading" :data="items" row-key="id" stripe @selection-change="onSelectionChange">
        <CustomTableColumn type="selection" width="48" align="center" />
        <CustomTableColumn label="STT" width="70" align="center">
          <template #default="{ $index }">{{ (page - 1) * limit + $index + 1 }}</template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_san_pham" label="Mã sản phẩm" min-width="140" show-overflow-tooltip />
        <CustomTableColumn prop="ten_san_pham" label="Tên sản phẩm" min-width="220" show-overflow-tooltip />
        <CustomTableColumn prop="phien_ban" label="Phiên bản" width="110" />
        <CustomTableColumn label="Số chỉ tiêu" width="120" align="center">
          <template #default="{ row }">{{ row.chi_tiet?.length || 0 }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="170">
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

      <Pagination v-model="page" v-model:page-size="limit" :total="total" :disabled="loading" @change="loadItems" />
    </CustomCard>

    <CustomDialog v-model="dialogOpen" :title="dialogTitle" :width="1080" @closed="onDialogClosed">
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="14">
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Mã sản phẩm" prop="ma_san_pham">
              <CustomInput v-model="form.ma_san_pham" placeholder="SP001" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Tên sản phẩm" prop="ten_san_pham">
              <CustomInput v-model="form.ten_san_pham" placeholder="Hạt nhựa PP" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Phiên bản" prop="phien_ban">
              <CustomInput v-model="form.phien_ban" placeholder="v1.0" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" class="full-control" :clearable="false">
                <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <div class="detail-head">
          <span>Chỉ tiêu kiểm tra</span>
          <CustomButton @click="addDetail">Thêm chỉ tiêu</CustomButton>
        </div>
        <p v-if="detailError" class="detail-error">{{ detailError }}</p>
        <div v-for="(item, index) in form.chi_tiet" :key="index" class="detail-block">
          <CustomRow :gutter="14">
            <CustomCol :xs="12" :sm="6">
              <CustomFormItem label="Hạng mục">
                <CustomInput v-model="item.hang_muc_kiem_tra" placeholder="Kích thước" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="6">
              <CustomFormItem label="Loại">
                <CustomSelect v-model="item.loai_tieu_chuan" class="full-control" :clearable="false">
                  <CustomOption v-for="option in kinds" :key="option.value" :label="option.label" :value="option.value" />
                </CustomSelect>
              </CustomFormItem>
            </CustomCol>
            <template v-if="item.loai_tieu_chuan === 'NUMERIC'">
              <CustomCol :xs="12" :sm="4">
                <CustomFormItem label="Định mức">
                  <CustomInput v-model="item.gia_tri_dinh_muc" placeholder="0" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="4">
                <CustomFormItem label="Dung sai trên">
                  <CustomInput v-model="item.dung_sai_tren" placeholder="0" />
                </CustomFormItem>
              </CustomCol>
              <CustomCol :xs="12" :sm="4">
                <CustomFormItem label="Dung sai dưới">
                  <CustomInput v-model="item.dung_sai_duoi" placeholder="0" />
                </CustomFormItem>
              </CustomCol>
            </template>
            <CustomCol v-else :xs="12" :sm="12">
              <CustomFormItem label="Tiêu chuẩn mô tả">
                <CustomInput v-model="item.tieu_chuan_mo_ta" placeholder="Không rách, không bẩn" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="6">
              <CustomFormItem label="Đơn vị tính">
                <CustomInput v-model="item.don_vi_tinh" placeholder="mm" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="6">
              <CustomFormItem label="Phương pháp">
                <CustomInput v-model="item.phuong_phap_kiem_tra" placeholder="Đo" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="6">
              <CustomFormItem label="Dụng cụ">
                <CustomInput v-model="item.dung_cu_thiet_bi" placeholder="Thước đo" />
              </CustomFormItem>
            </CustomCol>
            <CustomCol :xs="12" :sm="6" class="detail-remove">
              <CustomButton text type="danger" @click="removeDetail(index)">Xóa chỉ tiêu</CustomButton>
            </CustomCol>
          </CustomRow>
        </div>
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

  :deep(.el-input) { width: 320px; }
  :deep(.el-select) { width: 200px; }
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

.detail-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 4px 0 10px;
  font-weight: 600;
}

.detail-error {
  margin: 0 0 8px;
  color: var(--el-color-danger);
  font-size: 12px;
}

.detail-block {
  margin-bottom: 8px;
  padding: 8px 8px 0;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
}

.detail-remove {
  display: flex;
  align-items: center;
}

@media (max-width: 767px) {
  .filters {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 0.8fr) auto;
    gap: 6px;

    :deep(.el-input),
    :deep(.el-select) {
      width: 100%;
      min-width: 0;
    }
  }
}
</style>
