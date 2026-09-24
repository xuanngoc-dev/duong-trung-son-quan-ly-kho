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

const emptyForm = () => ({
  ma_nguyen_vat_lieu: '',
  ten_nguyen_vat_lieu: '',
  part_number: '',
  nha_cung_cap_id: null,
  quy_cach_spec: '',
  don_vi_tinh: '',
  tieu_chuan_kiem_tra: '',
  hinh_anh: '',
})

const items = ref([])
const suppliers = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)
const loading = ref(false)
const resetPageOnReload = ref(false)
const saving = ref(false)
const bulkDeleting = ref(false)
const keyword = ref('')
const supplierFilter = ref('')
const selectedRows = ref([])
const tableRef = ref()
const dialogOpen = ref(false)
const shouldReload = ref(false)
const formRef = ref()
const editingId = ref(null)
const form = reactive(emptyForm())
const imageUploading = ref(false)

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
      ? `Xóa ${selectedCount.value} nguyên vật liệu đã chọn`
      : 'Chọn nguyên vật liệu để xóa',
  },
])

const dialogTitle = computed(() => (editingId.value ? 'Sửa nguyên vật liệu' : 'Thêm nguyên vật liệu'))

const rules = {
  ma_nguyen_vat_lieu: [{ required: true, message: 'Nhập mã nguyên vật liệu', trigger: 'blur' }],
  ten_nguyen_vat_lieu: [{ required: true, message: 'Nhập tên nguyên vật liệu', trigger: 'blur' }],
  don_vi_tinh: [{ required: true, message: 'Nhập đơn vị tính', trigger: 'blur' }],
  hinh_anh: [
    {
      validator: (_rule, value, callback) => {
        if (!value || value.startsWith('/storage/')) {
          callback()
          return
        }
        try {
          const url = new URL(value)
          if (!['http:', 'https:'].includes(url.protocol)) callback(new Error('Link hình ảnh không hợp lệ'))
          else callback()
        } catch {
          callback(new Error('Link hình ảnh không hợp lệ'))
        }
      },
      trigger: 'blur',
    },
  ],
}

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || 'Không thể thực hiện thao tác.'
}

function supplierLabel(item) {
  if (!item) return ''
  return `${item.ma_nha_cung_cap} - ${item.ten_nha_cung_cap}`
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function searchItems() {
  page.value = 1
  loadItems()
}

async function loadSuppliers() {
  const { data } = await http.get('/nha-cung-cap', { params: { limit: 100 } })
  suppliers.value = data.data || []
}

async function loadItems() {
  loading.value = true
  selectedRows.value = []
  tableRef.value?.clearSelection?.()
  try {
    const { data } = await http.get('/danh-muc-nguyen-vat-lieu', {
      params: {
        q: keyword.value.trim() || undefined,
        nha_cung_cap_id: supplierFilter.value || undefined,
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
    ma_nguyen_vat_lieu: row.ma_nguyen_vat_lieu || '',
    ten_nguyen_vat_lieu: row.ten_nguyen_vat_lieu || '',
    part_number: row.part_number || '',
    nha_cung_cap_id: row.nha_cung_cap_id || null,
    quy_cach_spec: row.quy_cach_spec || '',
    don_vi_tinh: row.don_vi_tinh || '',
    tieu_chuan_kiem_tra: row.tieu_chuan_kiem_tra || '',
    hinh_anh: row.hinh_anh || '',
  })
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

async function saveItem() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    ma_nguyen_vat_lieu: form.ma_nguyen_vat_lieu.trim(),
    ten_nguyen_vat_lieu: form.ten_nguyen_vat_lieu.trim(),
    part_number: form.part_number.trim(),
    nha_cung_cap_id: form.nha_cung_cap_id || null,
    quy_cach_spec: form.quy_cach_spec.trim(),
    don_vi_tinh: form.don_vi_tinh.trim(),
    tieu_chuan_kiem_tra: form.tieu_chuan_kiem_tra.trim(),
    hinh_anh: form.hinh_anh.trim(),
  }
  try {
    const { data } = editingId.value
      ? await http.put(`/danh-muc-nguyen-vat-lieu/${editingId.value}`, payload)
      : await http.post('/danh-muc-nguyen-vat-lieu', payload)
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
    ? `nguyên vật liệu ${targets[0].ten_nguyen_vat_lieu}`
    : `${targets.length} nguyên vật liệu đã chọn`
  try {
    await ElMessageBox.confirm(`Xóa ${label}?`, 'Xóa nguyên vật liệu', {
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
      await http.delete(`/danh-muc-nguyen-vat-lieu/${row.id}`)
    }
    ElMessage.success(targets.length === 1 ? 'Đã xóa nguyên vật liệu.' : `Đã xóa ${targets.length} nguyên vật liệu.`)
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

async function uploadImage({ file }) {
  if (!file.type.startsWith('image/')) {
    ElMessage.error('File phải là hình ảnh.')
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    ElMessage.error('Hình ảnh tối đa 5MB.')
    return
  }

  imageUploading.value = true
  const body = new FormData()
  body.append('file', file)
  try {
    const { data } = await http.post('/danh-muc-nguyen-vat-lieu/anh', body)
    form.hinh_anh = data.pathFile || ''
  } catch (error) {
    ElMessage.error(errorMessage(error))
  } finally {
    imageUploading.value = false
  }
}

onMounted(() => {
  loadSuppliers().catch((error) => ElMessage.error(errorMessage(error)))
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
          placeholder="Tìm theo mã, tên, part number..."
          @keyup.enter="searchItems"
          @clear="searchItems"
        >
          <template #prefix><CustomIcon><Search /></CustomIcon></template>
        </CustomInput>
        <CustomSelect v-model="supplierFilter" clearable filterable placeholder="Tất cả nhà cung cấp">
          <CustomOption
            v-for="item in suppliers"
            :key="item.id"
            :label="supplierLabel(item)"
            :value="item.id"
          />
        </CustomSelect>
        <CustomButton type="primary" @click="searchItems">Tìm kiếm</CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="never" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh mục nguyên vật liệu</span>
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
        <CustomTableColumn label="Hình ảnh" width="90" align="center">
          <template #default="{ row }">
            <img v-if="row.hinh_anh" :src="row.hinh_anh" class="thumb" alt="" />
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_nguyen_vat_lieu" label="Mã" width="130" show-overflow-tooltip />
        <CustomTableColumn prop="ten_nguyen_vat_lieu" label="Tên nguyên vật liệu" min-width="200" show-overflow-tooltip />
        <CustomTableColumn prop="part_number" label="Part number" min-width="140" show-overflow-tooltip />
        <CustomTableColumn label="Nhà cung cấp" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">{{ supplierLabel(row.nha_cung_cap) }}</template>
        </CustomTableColumn>
        <CustomTableColumn prop="quy_cach_spec" label="Quy cách" min-width="180" show-overflow-tooltip />
        <CustomTableColumn prop="don_vi_tinh" label="Đơn vị tính" width="120" />
        <CustomTableColumn prop="tieu_chuan_kiem_tra" label="Tiêu chuẩn kiểm tra" min-width="200" show-overflow-tooltip />
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
        <div class="image-upload">
          <CustomFormItem label="Hình ảnh">
            <el-upload
              class="avatar-uploader"
              accept="image/*"
              :show-file-list="false"
              :disabled="imageUploading"
              :http-request="uploadImage"
            >
              <img v-if="form.hinh_anh" :src="form.hinh_anh" class="avatar" alt="" />
              <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
            </el-upload>
          </CustomFormItem>
        </div>
        <CustomRow :gutter="14">
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Đường dẫn ảnh" prop="hinh_anh">
              <CustomInput
                v-model="form.hinh_anh"
                clearable
                placeholder="https:// hoặc /storage/..."
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Mã nguyên vật liệu" prop="ma_nguyen_vat_lieu">
              <CustomInput v-model="form.ma_nguyen_vat_lieu" placeholder="NVL001" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Tên nguyên vật liệu" prop="ten_nguyen_vat_lieu">
              <CustomInput v-model="form.ten_nguyen_vat_lieu" placeholder="Hạt nhựa PP" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Part number" prop="part_number">
              <CustomInput v-model="form.part_number" placeholder="PN-1001" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Đơn vị tính" prop="don_vi_tinh">
              <CustomInput v-model="form.don_vi_tinh" placeholder="kg" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Nhà cung cấp" prop="nha_cung_cap_id">
              <CustomSelect v-model="form.nha_cung_cap_id" class="full-control" clearable filterable placeholder="Chọn nhà cung cấp">
                <CustomOption
                  v-for="item in suppliers"
                  :key="item.id"
                  :label="supplierLabel(item)"
                  :value="item.id"
                />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Quy cách" prop="quy_cach_spec">
              <CustomInput v-model="form.quy_cach_spec" placeholder="Quy cách, thông số" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Tiêu chuẩn kiểm tra" prop="tieu_chuan_kiem_tra">
              <CustomInput v-model="form.tieu_chuan_kiem_tra" placeholder="Tiêu chuẩn nghiệm thu" />
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
  :deep(.el-select) { width: 260px; }
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

.thumb {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
  vertical-align: middle;
}

.image-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 8px;

  :deep(.el-form-item) {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 12px;
  }

  :deep(.el-form-item__label) {
    justify-content: center;
    width: auto;
    padding: 0;
  }

  :deep(.el-form-item__content) {
    justify-content: center;
  }
}

.avatar-uploader :deep(.el-upload) {
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  cursor: pointer;
  overflow: hidden;
}

.avatar-uploader :deep(.el-upload:hover) {
  border-color: var(--el-color-primary);
}

.avatar,
.avatar-uploader-icon {
  width: 120px;
  height: 120px;
}

.avatar {
  display: block;
  object-fit: cover;
}

.avatar-uploader-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: var(--el-text-color-secondary);
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
