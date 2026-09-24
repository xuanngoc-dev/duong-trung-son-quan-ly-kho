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
  CustomSwitch,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import BulkActionBar from '@/components/BulkActionBar.vue'
import Pagination from '@/components/Pagination.vue'

const statuses = [
  { value: 'dang_su_dung', label: 'Đang sử dụng' },
  { value: 'ngung_su_dung', label: 'Ngừng sử dụng' },
]

const emptyForm = () => ({
  ten_nha_cung_cap: '',
  ma_nha_cung_cap: '',
  email_nha_cung_cap: '',
  sdt_nha_cung_cap: '',
  ten_nguoi_lien_he: '',
  email_nguoi_lien_he: '',
  sdt_nguoi_lien_he: '',
  website: '',
  dia_chi: '',
  ma_so_thue: '',
  so_tai_khoan: '',
  ngan_hang: '',
  trang_thai: 'dang_su_dung',
  ghi_chu: '',
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
      ? `Xóa ${selectedCount.value} nhà cung cấp đã chọn`
      : 'Chọn nhà cung cấp để xóa',
  },
])

const dialogTitle = computed(() => (editingId.value ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp'))

const optionalEmail = (message) => ({
  validator: (_rule, value, callback) => {
    if (!value) {
      callback()
      return
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) callback(new Error(message))
    else callback()
  },
  trigger: 'blur',
})

const rules = {
  ten_nha_cung_cap: [{ required: true, message: 'Nhập tên nhà cung cấp', trigger: 'blur' }],
  ma_nha_cung_cap: [{ required: true, message: 'Nhập mã nhà cung cấp', trigger: 'blur' }],
  email_nha_cung_cap: [optionalEmail('Email nhà cung cấp không hợp lệ')],
  email_nguoi_lien_he: [optionalEmail('Email người liên hệ không hợp lệ')],
  trang_thai: [{ required: true, message: 'Chọn trạng thái', trigger: 'change' }],
}

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || 'Không thể thực hiện thao tác.'
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
    const { data } = await http.get('/nha-cung-cap', {
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
    ten_nha_cung_cap: row.ten_nha_cung_cap || '',
    ma_nha_cung_cap: row.ma_nha_cung_cap || '',
    email_nha_cung_cap: row.email_nha_cung_cap || '',
    sdt_nha_cung_cap: row.sdt_nha_cung_cap || '',
    ten_nguoi_lien_he: row.ten_nguoi_lien_he || '',
    email_nguoi_lien_he: row.email_nguoi_lien_he || '',
    sdt_nguoi_lien_he: row.sdt_nguoi_lien_he || '',
    website: row.website || '',
    dia_chi: row.dia_chi || '',
    ma_so_thue: row.ma_so_thue || '',
    so_tai_khoan: row.so_tai_khoan || '',
    ngan_hang: row.ngan_hang || '',
    trang_thai: row.trang_thai || 'dang_su_dung',
    ghi_chu: row.ghi_chu || '',
  })
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function payloadFrom(source) {
  return {
    ten_nha_cung_cap: source.ten_nha_cung_cap.trim(),
    ma_nha_cung_cap: source.ma_nha_cung_cap.trim(),
    email_nha_cung_cap: source.email_nha_cung_cap.trim(),
    sdt_nha_cung_cap: source.sdt_nha_cung_cap.trim(),
    ten_nguoi_lien_he: source.ten_nguoi_lien_he.trim(),
    email_nguoi_lien_he: source.email_nguoi_lien_he.trim(),
    sdt_nguoi_lien_he: source.sdt_nguoi_lien_he.trim(),
    website: source.website.trim(),
    dia_chi: source.dia_chi.trim(),
    ma_so_thue: source.ma_so_thue.trim(),
    so_tai_khoan: source.so_tai_khoan.trim(),
    ngan_hang: source.ngan_hang.trim(),
    trang_thai: source.trang_thai,
    ghi_chu: source.ghi_chu.trim(),
  }
}

async function saveItem() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = payloadFrom(form)
  try {
    const { data } = editingId.value
      ? await http.put(`/nha-cung-cap/${editingId.value}`, payload)
      : await http.post('/nha-cung-cap', payload)
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
  const label = targets.length === 1
    ? `nhà cung cấp ${targets[0].ten_nha_cung_cap}`
    : `${targets.length} nhà cung cấp đã chọn`
  try {
    await ElMessageBox.confirm(`Xóa ${label}?`, 'Xóa nhà cung cấp', {
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
      await http.delete(`/nha-cung-cap/${row.id}`)
    }
    ElMessage.success(targets.length === 1 ? 'Đã xóa nhà cung cấp.' : `Đã xóa ${targets.length} nhà cung cấp.`)
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
    const { data } = await http.put(`/nha-cung-cap/${row.id}`, {
      ten_nha_cung_cap: row.ten_nha_cung_cap,
      ma_nha_cung_cap: row.ma_nha_cung_cap,
      trang_thai: value,
    })
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
          placeholder="Tìm theo tên, mã, email, SĐT..."
          @keyup.enter="searchItems"
          @clear="searchItems"
        >
          <template #prefix><CustomIcon><Search /></CustomIcon></template>
        </CustomInput>
        <CustomSelect v-model="statusFilter" clearable placeholder="Tất cả trạng thái">
          <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
        </CustomSelect>
        <CustomButton type="primary" @click="searchItems">Tìm kiếm</CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="never" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách nhà cung cấp</span>
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
        <CustomTableColumn prop="ma_nha_cung_cap" label="Mã" width="110" show-overflow-tooltip />
        <CustomTableColumn prop="ten_nha_cung_cap" label="Tên nhà cung cấp" min-width="220" show-overflow-tooltip />
        <CustomTableColumn prop="email_nha_cung_cap" label="Email" min-width="200" show-overflow-tooltip />
        <CustomTableColumn prop="sdt_nha_cung_cap" label="SĐT" width="140" />
        <CustomTableColumn prop="ten_nguoi_lien_he" label="Người liên hệ" min-width="160" show-overflow-tooltip />
        <CustomTableColumn prop="email_nguoi_lien_he" label="Email người liên hệ" min-width="200" show-overflow-tooltip />
        <CustomTableColumn prop="sdt_nguoi_lien_he" label="SĐT người liên hệ" width="160" />
        <CustomTableColumn prop="website" label="Website" min-width="160" show-overflow-tooltip />
        <CustomTableColumn prop="dia_chi" label="Địa chỉ" min-width="220" show-overflow-tooltip />
        <CustomTableColumn prop="ma_so_thue" label="Mã số thuế" width="140" />
        <CustomTableColumn prop="so_tai_khoan" label="Số tài khoản" width="160" />
        <CustomTableColumn prop="ngan_hang" label="Ngân hàng" min-width="140" show-overflow-tooltip />
        <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="180" show-overflow-tooltip />
        <CustomTableColumn label="Trạng thái" width="160" align="center">
          <template #default="{ row }">
            <CustomSwitch
              v-if="row.id"
              :model-value="row.trang_thai"
              active-value="dang_su_dung"
              inactive-value="ngung_su_dung"
              active-text="Dùng"
              inactive-text="Ngừng"
              :loading="statusUpdatingId === row.id"
              :disabled="statusUpdatingId === row.id"
              @change="(value) => updateStatus(row, value)"
            />
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
            <CustomFormItem label="Tên nhà cung cấp" prop="ten_nha_cung_cap">
              <CustomInput v-model="form.ten_nha_cung_cap" placeholder="Công ty TNHH ABC" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Mã nhà cung cấp" prop="ma_nha_cung_cap">
              <CustomInput v-model="form.ma_nha_cung_cap" placeholder="NCC001" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Email nhà cung cấp" prop="email_nha_cung_cap">
              <CustomInput v-model="form.email_nha_cung_cap" placeholder="contact@abc.vn" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="SĐT nhà cung cấp" prop="sdt_nha_cung_cap">
              <CustomInput v-model="form.sdt_nha_cung_cap" placeholder="0901234567" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Tên người liên hệ" prop="ten_nguoi_lien_he">
              <CustomInput v-model="form.ten_nguoi_lien_he" placeholder="Nguyễn Văn A" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Email người liên hệ" prop="email_nguoi_lien_he">
              <CustomInput v-model="form.email_nguoi_lien_he" placeholder="a.nguyen@abc.vn" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="SĐT người liên hệ" prop="sdt_nguoi_lien_he">
              <CustomInput v-model="form.sdt_nguoi_lien_he" placeholder="0901234567" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Website" prop="website">
              <CustomInput v-model="form.website" placeholder="https://abc.vn" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Địa chỉ" prop="dia_chi">
              <CustomInput v-model="form.dia_chi" placeholder="Số nhà, đường, quận, tỉnh" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Mã số thuế" prop="ma_so_thue">
              <CustomInput v-model="form.ma_so_thue" placeholder="0123456789" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Số tài khoản" prop="so_tai_khoan">
              <CustomInput v-model="form.so_tai_khoan" placeholder="0123456789" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Ngân hàng" prop="ngan_hang">
              <CustomInput v-model="form.ngan_hang" placeholder="Vietcombank" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" class="full-control" :clearable="false">
                <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
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

.full-control {
  width: 100%;
}

@media (max-width: 767px) {
  .filters {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(0, 0.9fr) auto;
    gap: 6px;

    :deep(.el-input),
    :deep(.el-select) {
      width: 100%;
      min-width: 0;
    }

    :deep(.el-button) {
      padding-inline: 8px;
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
