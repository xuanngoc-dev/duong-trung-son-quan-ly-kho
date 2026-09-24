<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
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
  CustomSwitch,
  CustomTable,
  CustomTableColumn,
  CustomTooltip,
} from '@/components/element'
import BulkActionBar from '@/components/BulkActionBar.vue'
import Pagination from '@/components/Pagination.vue'

const conclusions = [
  { value: 'OK', label: 'OK' },
  { value: 'NG', label: 'NG' },
]

const emptyDetail = () => ({ hang_muc: '', ket_qua: 'OK', ghi_chu: '' })

function nowDateTime() {
  const date = new Date()
  const pad = (value) => String(value).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
}

const emptyForm = () => ({
  lo_nguyen_vat_lieu_id: null,
  ma_qr_barcode: '',
  so_luong_lay_mau: '',
  noi_dung_chi_tiet: [],
  nguoi_kiem_tra: '',
  ngay_kiem_tra: nowDateTime(),
  ket_luan: 'OK',
  ghi_chu: '',
  hinh_anh_dinh_kem: [],
})

const items = ref([])
const lots = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)
const loading = ref(false)
const resetPageOnReload = ref(false)
const saving = ref(false)
const bulkDeleting = ref(false)
const keyword = ref('')
const conclusionFilter = ref('')
const lotFilter = ref('')
const supplierFilter = ref([])
const dateRange = ref([])
const suppliers = ref([])
const selectedRows = ref([])
const tableRef = ref()
const dialogOpen = ref(false)
const shouldReload = ref(false)
const formRef = ref()
const editingId = ref(null)
const form = reactive(emptyForm())
const conclusionUpdatingId = ref(null)
const detailError = ref('')
const imageUploading = ref(false)
const imagePathText = ref('')

const selectedCount = computed(() => selectedRows.value.length)
const bulkActions = computed(() => [
  {
    key: 'delete',
    label: 'Xóa',
    type: 'danger',
    badge: selectedCount.value,
    badgeType: 'danger',
    loading: bulkDeleting.value,
    tooltip: selectedCount.value ? `Xóa ${selectedCount.value} phiếu đã chọn` : 'Chọn phiếu để xóa',
  },
])
const dialogTitle = computed(() => (editingId.value ? 'Sửa phiếu kiểm tra IQC' : 'Thêm phiếu kiểm tra IQC'))

const rules = {
  lo_nguyen_vat_lieu_id: [{ required: true, message: 'Chọn lô nguyên vật liệu', trigger: 'change' }],
  so_luong_lay_mau: [
    {
      validator: (_rule, value, callback) => {
        if (value === '' || value == null) {
          callback(new Error('Nhập số lượng lấy mẫu'))
          return
        }
        if (!/^\d+(\.\d{1,4})?$/.test(String(value).trim())) callback(new Error('Số lượng lấy mẫu không hợp lệ'))
        else callback()
      },
      trigger: 'blur',
    },
  ],
  nguoi_kiem_tra: [{ required: true, message: 'Nhập người kiểm tra', trigger: 'blur' }],
  ngay_kiem_tra: [{ required: true, message: 'Chọn ngày kiểm tra', trigger: 'change' }],
  ket_luan: [{ required: true, message: 'Chọn kết luận', trigger: 'change' }],
}

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || 'Không thể thực hiện thao tác.'
}

function lotLabel(lot) {
  if (!lot) return ''
  const material = lot.nguyen_vat_lieu
  const name = material ? `${material.ma_nguyen_vat_lieu} - ${material.ten_nguyen_vat_lieu}` : ''
  return name ? `${lot.so_lo_batch} · ${name}` : lot.so_lo_batch
}

function formatQuantity(value) {
  if (value == null || value === '') return ''
  const number = Number(value)
  if (Number.isNaN(number)) return value
  return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 4 }).format(number)
}

function formatDateTime(value) {
  if (!value) return ''
  const [date, time = ''] = String(value).replace('T', ' ').slice(0, 19).split(' ')
  const [year, month, day] = date.split('-')
  if (!year || !month || !day) return value
  return `${day}/${month}/${year}${time ? ` ${time.slice(0, 5)}` : ''}`
}

function detailText(rows) {
  return (rows || []).map((item) => `${item.hang_muc}: ${item.ket_qua}`).join('; ')
}

function imageList(value) {
  if (Array.isArray(value)) return value.map((item) => String(item || '').trim()).filter(Boolean)
  if (!value) return []
  const text = String(value).trim()
  if (!text) return []
  try {
    const parsed = JSON.parse(text)
    if (Array.isArray(parsed)) return parsed.map((item) => String(item || '').trim()).filter(Boolean)
  } catch {
    return [text]
  }
  return [text]
}

function formatImagePaths(list) {
  const paths = imageList(list)
  return paths.map((path, index) => (index < paths.length - 1 ? `${path},` : path)).join('\n')
}

function parseImagePaths(text) {
  return String(text || '')
    .split(/[\n,]+/)
    .map((item) => item.trim())
    .filter(Boolean)
}

function setImagePaths(list) {
  form.hinh_anh_dinh_kem = imageList(list)
  imagePathText.value = formatImagePaths(form.hinh_anh_dinh_kem)
}

function onImagePathInput() {
  form.hinh_anh_dinh_kem = parseImagePaths(imagePathText.value)
}

watch(imagePathText, onImagePathInput)

async function copyImagePath(path) {
  if (!path) return
  try {
    await navigator.clipboard.writeText(path)
  } catch {
    const input = document.createElement('textarea')
    input.value = path
    input.setAttribute('readonly', '')
    input.style.position = 'fixed'
    input.style.left = '-9999px'
    document.body.appendChild(input)
    input.select()
    const copied = document.execCommand('copy')
    input.remove()
    if (!copied) {
      ElMessage.error('Không sao chép được đường dẫn ảnh.')
      return
    }
  }
  ElMessage.success('Đã sao chép đường dẫn ảnh.')
}

function validImagePath(value) {
  if (!value || value.startsWith('/storage/')) return true
  try {
    const url = new URL(value)
    return ['http:', 'https:'].includes(url.protocol)
  } catch {
    return false
  }
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function searchItems() {
  page.value = 1
  loadItems()
}

function supplierLabel(item) {
  if (!item) return ''
  return `${item.ma_nha_cung_cap} - ${item.ten_nha_cung_cap}`
}

async function loadLots() {
  const { data } = await http.get('/lo-nguyen-vat-lieu', { params: { limit: 100 } })
  lots.value = data.data || []
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
    const { data } = await http.get('/phieu-kiem-tra-iqc', {
      params: {
        q: keyword.value.trim() || undefined,
        ket_luan: conclusionFilter.value || undefined,
        lo_nguyen_vat_lieu_id: lotFilter.value || undefined,
        nha_cung_cap_id: supplierFilter.value.length ? supplierFilter.value : undefined,
        tu_ngay: dateRange.value?.[0] || undefined,
        den_ngay: dateRange.value?.[1] || undefined,
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
  detailError.value = ''
  Object.assign(form, emptyForm(), { noi_dung_chi_tiet: [] })
  imagePathText.value = ''
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
    lo_nguyen_vat_lieu_id: row.lo_nguyen_vat_lieu_id || null,
    ma_qr_barcode: row.ma_qr_barcode || '',
    so_luong_lay_mau: row.so_luong_lay_mau == null ? '' : String(Number(row.so_luong_lay_mau)),
    noi_dung_chi_tiet: (row.noi_dung_chi_tiet || []).map((item) => ({
      hang_muc: item.hang_muc || '',
      ket_qua: item.ket_qua || 'OK',
      ghi_chu: item.ghi_chu || '',
    })),
    nguoi_kiem_tra: row.nguoi_kiem_tra || '',
    ngay_kiem_tra: row.ngay_kiem_tra || nowDateTime(),
    ket_luan: row.ket_luan || 'OK',
    ghi_chu: row.ghi_chu || '',
    hinh_anh_dinh_kem: imageList(row.hinh_anh_dinh_kem),
  })
  imagePathText.value = formatImagePaths(form.hinh_anh_dinh_kem)
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function addDetail() {
  form.noi_dung_chi_tiet.push(emptyDetail())
  detailError.value = ''
}

function removeDetail(index) {
  form.noi_dung_chi_tiet.splice(index, 1)
}

function collectedDetails() {
  const rows = []
  for (const item of form.noi_dung_chi_tiet) {
    const hangMuc = item.hang_muc.trim()
    const ghiChu = item.ghi_chu.trim()
    if (!hangMuc && !ghiChu) continue
    if (!hangMuc) return null
    rows.push({ hang_muc: hangMuc, ket_qua: item.ket_qua || 'OK', ghi_chu: ghiChu })
  }
  return rows
}

function payloadFrom(source, details) {
  return {
    lo_nguyen_vat_lieu_id: source.lo_nguyen_vat_lieu_id,
    ma_qr_barcode: String(source.ma_qr_barcode || '').trim(),
    so_luong_lay_mau: String(source.so_luong_lay_mau).trim(),
    noi_dung_chi_tiet: details,
    nguoi_kiem_tra: String(source.nguoi_kiem_tra).trim(),
    ngay_kiem_tra: source.ngay_kiem_tra,
    ket_luan: source.ket_luan,
    ghi_chu: String(source.ghi_chu || '').trim(),
    hinh_anh_dinh_kem: imageList(source.hinh_anh_dinh_kem),
  }
}

async function saveItem() {
  onImagePathInput()
  const valid = await formRef.value?.validate().catch(() => false)
  const details = collectedDetails()
  detailError.value = details ? '' : 'Nhập hạng mục kiểm tra.'
  const images = imageList(form.hinh_anh_dinh_kem)
  if (images.some((path) => !validImagePath(path))) {
    ElMessage.error('Link hình ảnh không hợp lệ.')
    return
  }
  if (!valid || !details) return

  saving.value = true
  try {
    const { data } = editingId.value
      ? await http.put(`/phieu-kiem-tra-iqc/${editingId.value}`, payloadFrom(form, details))
      : await http.post('/phieu-kiem-tra-iqc', payloadFrom(form, details))
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
  const label = targets.length === 1 ? 'phiếu kiểm tra này' : `${targets.length} phiếu đã chọn`
  try {
    await ElMessageBox.confirm(`Xóa ${label}?`, 'Xóa phiếu kiểm tra IQC', {
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
      await http.delete(`/phieu-kiem-tra-iqc/${row.id}`)
    }
    ElMessage.success(targets.length === 1 ? 'Đã xóa phiếu kiểm tra IQC.' : `Đã xóa ${targets.length} phiếu.`)
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

async function updateConclusion(row, value) {
  if (!row?.id || conclusionUpdatingId.value === row.id || row.ket_luan === value) return
  const previous = row.ket_luan
  row.ket_luan = value
  conclusionUpdatingId.value = row.id
  try {
    const { data } = await http.put(`/phieu-kiem-tra-iqc/${row.id}`, {
      lo_nguyen_vat_lieu_id: row.lo_nguyen_vat_lieu_id,
      ma_qr_barcode: row.ma_qr_barcode || '',
      so_luong_lay_mau: row.so_luong_lay_mau,
      noi_dung_chi_tiet: row.noi_dung_chi_tiet || [],
      nguoi_kiem_tra: row.nguoi_kiem_tra,
      ngay_kiem_tra: row.ngay_kiem_tra,
      ket_luan: value,
      ghi_chu: row.ghi_chu || '',
      hinh_anh_dinh_kem: imageList(row.hinh_anh_dinh_kem),
    })
    ElMessage.success(data.message || 'Đã cập nhật kết luận.')
  } catch (error) {
    row.ket_luan = previous
    ElMessage.error(errorMessage(error))
  } finally {
    conclusionUpdatingId.value = null
  }
}

function removeImage(index) {
  const paths = imageList(form.hinh_anh_dinh_kem)
  paths.splice(index, 1)
  setImagePaths(paths)
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
  if (parseImagePaths(imagePathText.value).length >= 20) {
    ElMessage.error('Tối đa 20 hình ảnh.')
    return
  }

  imageUploading.value = true
  const body = new FormData()
  body.append('file', file)
  try {
    const { data } = await http.post('/phieu-kiem-tra-iqc/anh', body)
    if (data.pathFile) setImagePaths([...parseImagePaths(imagePathText.value), data.pathFile])
  } catch (error) {
    ElMessage.error(errorMessage(error))
  } finally {
    imageUploading.value = false
  }
}

onMounted(() => {
  loadLots().catch((error) => ElMessage.error(errorMessage(error)))
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
          class="keyword-filter"
          clearable
          placeholder="Tìm theo số lô, mã QR, người kiểm tra..."
          @keyup.enter="searchItems"
          @clear="searchItems"
        >
          <template #prefix><CustomIcon><Search /></CustomIcon></template>
        </CustomInput>
        <CustomSelect v-model="lotFilter" clearable filterable placeholder="Tất cả lô">
          <CustomOption v-for="lot in lots" :key="lot.id" :label="lotLabel(lot)" :value="lot.id" />
        </CustomSelect>
        <CustomSelect
          v-model="supplierFilter"
          class="supplier-filter"
          multiple
          collapse-tags
          collapse-tags-tooltip
          clearable
          filterable
          placeholder="Nhà cung cấp"
        >
          <CustomOption v-for="item in suppliers" :key="item.id" :label="supplierLabel(item)" :value="item.id" />
        </CustomSelect>
        <div class="date-filter">
          <CustomDatePicker
            v-model="dateRange"
            type="daterange"
            value-format="YYYY-MM-DD"
            range-separator="-"
            start-placeholder="Từ ngày"
            end-placeholder="Đến ngày"
            clearable
          />
        </div>
        <CustomSelect v-model="conclusionFilter" clearable placeholder="Tất cả kết luận">
          <CustomOption v-for="item in conclusions" :key="item.value" :label="item.label" :value="item.value" />
        </CustomSelect>
        <CustomButton type="primary" @click="searchItems">Tìm kiếm</CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="never" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Phiếu kiểm tra IQC</span>
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
        <CustomTableColumn label="Số lô" min-width="150">
          <template #default="{ row }">
            <div v-if="row.id" class="lot-cell">
              <span class="lot-text">{{ row.lo_nguyen_vat_lieu?.so_lo_batch }}</span>
              <span v-if="row.ket_luan === 'OK'" class="lot-mark lot-mark--ok" title="OK">
                <CustomIcon><Check /></CustomIcon>
              </span>
              <span v-else class="lot-mark lot-mark--ng" title="NG">
                <CustomIcon><Close /></CustomIcon>
              </span>
            </div>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Nguyên vật liệu" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.lo_nguyen_vat_lieu?.nguyen_vat_lieu?.ma_nguyen_vat_lieu }}
            {{ row.lo_nguyen_vat_lieu?.nguyen_vat_lieu?.ten_nguyen_vat_lieu }}
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ma_qr_barcode" label="Mã QR" min-width="140" show-overflow-tooltip />
        <CustomTableColumn label="SL lấy mẫu" width="120" align="right">
          <template #default="{ row }">{{ formatQuantity(row.so_luong_lay_mau) }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Nội dung kiểm tra" min-width="220" show-overflow-tooltip>
          <template #default="{ row }">{{ detailText(row.noi_dung_chi_tiet) }}</template>
        </CustomTableColumn>
        <CustomTableColumn prop="nguoi_kiem_tra" label="Người kiểm tra" min-width="150" show-overflow-tooltip />
        <CustomTableColumn label="Ngày kiểm tra" width="150">
          <template #default="{ row }">{{ formatDateTime(row.ngay_kiem_tra) }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Kết luận" width="120" align="center">
          <template #default="{ row }">
            <CustomSwitch
              v-if="row.id"
              :model-value="row.ket_luan"
              active-value="OK"
              inactive-value="NG"
              active-text="OK"
              inactive-text="NG"
              :loading="conclusionUpdatingId === row.id"
              :disabled="conclusionUpdatingId === row.id"
              @change="(value) => updateConclusion(row, value)"
            />
          </template>
        </CustomTableColumn>
        <CustomTableColumn prop="ghi_chu" label="Ghi chú" min-width="160" show-overflow-tooltip />
        <CustomTableColumn label="Hình ảnh" min-width="160">
          <template #default="{ row }">
            <div v-if="imageList(row.hinh_anh_dinh_kem).length" class="thumb-list">
              <el-image
                v-for="(src, index) in imageList(row.hinh_anh_dinh_kem)"
                :key="`${src}-${index}`"
                :src="src"
                :preview-src-list="imageList(row.hinh_anh_dinh_kem)"
                :initial-index="index"
                fit="cover"
                preview-teleported
                class="thumb"
              >
                <template #toolbar="{ actions, prev, next, activeIndex }">
                  <el-icon title="Ảnh trước" @click.stop="prev"><ArrowLeft /></el-icon>
                  <el-icon title="Ảnh sau" @click.stop="next"><ArrowRight /></el-icon>
                  <i class="el-image-viewer__actions__divider" />
                  <el-icon title="Thu nhỏ" @click.stop="actions('zoomOut')"><ZoomOut /></el-icon>
                  <el-icon title="Phóng to" @click.stop="actions('zoomIn')"><ZoomIn /></el-icon>
                  <i class="el-image-viewer__actions__divider" />
                  <el-icon title="Xoay trái" @click.stop="actions('anticlockwise')"><RefreshLeft /></el-icon>
                  <el-icon title="Xoay phải" @click.stop="actions('clockwise')"><RefreshRight /></el-icon>
                  <i class="el-image-viewer__actions__divider" />
                  <el-icon
                    title="Sao chép đường dẫn"
                    @click.stop="copyImagePath(imageList(row.hinh_anh_dinh_kem)[activeIndex])"
                  >
                    <CopyDocument />
                  </el-icon>
                </template>
              </el-image>
            </div>
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
        <div class="image-upload">
          <CustomFormItem label="Hình ảnh đính kèm">
            <div class="image-list">
              <div v-for="(src, index) in form.hinh_anh_dinh_kem" :key="`${src}-${index}`" class="image-item">
                <img :src="src" class="avatar" alt="" />
                <CustomButton class="image-remove" text circle type="danger" @click="removeImage(index)">
                  <CustomIcon><Delete /></CustomIcon>
                </CustomButton>
              </div>
              <el-upload
                class="avatar-uploader"
                accept="image/*"
                multiple
                :show-file-list="false"
                :disabled="imageUploading"
                :http-request="uploadImage"
              >
                <el-icon class="avatar-uploader-icon"><Plus /></el-icon>
              </el-upload>
            </div>
          </CustomFormItem>
        </div>
        <CustomRow :gutter="14">
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Lô nguyên vật liệu" prop="lo_nguyen_vat_lieu_id">
              <CustomSelect v-model="form.lo_nguyen_vat_lieu_id" class="full-control" filterable placeholder="Chọn lô">
                <CustomOption v-for="lot in lots" :key="lot.id" :label="lotLabel(lot)" :value="lot.id" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Mã QR / barcode" prop="ma_qr_barcode">
              <CustomInput v-model="form.ma_qr_barcode" placeholder="Quét hoặc nhập mã" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Số lượng lấy mẫu" prop="so_luong_lay_mau">
              <CustomInput v-model="form.so_luong_lay_mau" placeholder="0" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Người kiểm tra" prop="nguoi_kiem_tra">
              <CustomInput v-model="form.nguoi_kiem_tra" placeholder="Nguyễn Văn A" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Ngày kiểm tra" prop="ngay_kiem_tra">
              <CustomDatePicker
                v-model="form.ngay_kiem_tra"
                class="full-control"
                type="datetime"
                value-format="YYYY-MM-DD HH:mm:ss"
                placeholder="Chọn ngày giờ"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Kết luận" prop="ket_luan">
              <CustomSelect v-model="form.ket_luan" class="full-control" :clearable="false">
                <CustomOption v-for="item in conclusions" :key="item.value" :label="item.label" :value="item.value" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :xs="12" :sm="6">
            <CustomFormItem label="Ghi chú" prop="ghi_chu">
              <CustomInput v-model="form.ghi_chu" placeholder="Ghi chú thêm" />
            </CustomFormItem>
          </CustomCol>
        </CustomRow>

        <div class="detail-head">
          <span>Nội dung kiểm tra</span>
          <CustomButton @click="addDetail">Thêm hạng mục</CustomButton>
        </div>
        <p v-if="detailError" class="detail-error">{{ detailError }}</p>
        <CustomRow v-for="(item, index) in form.noi_dung_chi_tiet" :key="index" :gutter="14" class="detail-row">
          <CustomCol :xs="12" :sm="8">
            <CustomInput v-model="item.hang_muc" placeholder="Hạng mục" />
          </CustomCol>
          <CustomCol :xs="12" :sm="4">
            <CustomSelect v-model="item.ket_qua" class="full-control" :clearable="false">
              <CustomOption v-for="option in conclusions" :key="option.value" :label="option.label" :value="option.value" />
            </CustomSelect>
          </CustomCol>
          <CustomCol :xs="12" :sm="10">
            <CustomInput v-model="item.ghi_chu" placeholder="Ghi chú hạng mục" />
          </CustomCol>
          <CustomCol :xs="12" :sm="2" class="detail-remove">
            <CustomButton text type="danger" @click="removeDetail(index)">Xóa</CustomButton>
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
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  min-width: 0;

  :deep(.el-select) { width: 200px; }
}

.keyword-filter {
  width: 230px;
}

.supplier-filter {
  width: 260px;
}

.date-filter {
  flex: none;
  width: 220px;

  :deep(.el-date-editor) {
    width: 100%;
  }
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

.lot-cell {
  display: inline-flex;
  align-items: flex-start;
  max-width: 100%;
  gap: 2px;
}

.lot-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  width: 13px;
  height: 13px;
  margin-top: 0px;
  border-radius: 10px;
  color: #fff;
  font-weight: 700;
  line-height: 1;
}

.lot-mark :deep(.el-icon) {
  font-size: 9px;
  font-weight: 700;
}

.lot-mark :deep(svg) {
  stroke: currentColor;
  stroke-width: 2.5px;
}

.lot-mark--ok {
  background: var(--el-color-success);
}

.lot-mark--ng {
  background: var(--el-color-danger);
}

.full-control {
  width: 100%;
}

.thumb-list {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 4px;
  overflow-x: auto;
}

.thumb {
  flex: none;
  width: 40px;
  height: 40px;
  border-radius: 4px;
  cursor: pointer;

  :deep(.el-image__inner) {
    border-radius: 4px;
  }
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
    flex-direction: column;
    align-items: center;
  }
}

.image-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}

.image-item {
  position: relative;
}

.image-remove {
  position: absolute;
  top: 4px;
  right: 4px;
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
.avatar-uploader-icon,
.avatar-empty {
  width: 120px;
  height: 120px;
}

.avatar {
  display: block;
  object-fit: cover;
  border-radius: 8px;
}

.avatar-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed var(--el-border-color);
  border-radius: 8px;
  color: var(--el-text-color-secondary);
  font-size: 12px;
}

.avatar-uploader-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: var(--el-text-color-secondary);
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

.detail-row {
  margin-bottom: 8px;
}

.detail-remove {
  display: flex;
  align-items: center;
}

@media (max-width: 767px) {
  .filters {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 6px;

    :deep(.el-input),
    :deep(.el-select),
    :deep(.el-date-editor) {
      width: 100%;
      min-width: 0;
    }

    .keyword-filter,
    .supplier-filter,
    .date-filter,
    .date-filter :deep(.el-date-editor) {
      width: 100%;
    }
  }

  .card-header :deep(.el-button) {
    padding-inline: 8px;
  }
}
</style>
