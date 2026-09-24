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
  CustomTag,
  CustomTooltip,
} from '@/components/element'
import BulkActionBar from '@/components/BulkActionBar.vue'
import Pagination from '@/components/Pagination.vue'

const roles = [
  { value: 'admin', label: 'Quản trị', type: 'danger' },
  { value: 'leader', label: 'Trưởng nhóm', type: 'warning' },
  { value: 'qc', label: 'QC', type: 'primary' },
  { value: 'user', label: 'Nhân viên', type: 'info' },
]

const statuses = [
  { value: 'hoat_dong', label: 'Hoạt động' },
  { value: 'ngung_hoat_dong', label: 'Ngừng hoạt động' },
]

const users = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(10)
const loading = ref(false)
const resetPageOnReload = ref(false)
const saving = ref(false)
const bulkDeleting = ref(false)
const keyword = ref('')
const roleFilter = ref('')
const selectedRows = ref([])
const tableRef = ref()
const dialogOpen = ref(false)
const shouldReload = ref(false)
const formRef = ref()
const editingId = ref(null)
const form = reactive({
  name: '',
  email: '',
  password: '',
  confirmation: '',
  role: 'user',
  trang_thai: 'hoat_dong',
})
const statusUpdatingId = ref(null)
const passwordOpen = ref(false)
const passwordSaving = ref(false)
const passwordFormRef = ref()
const passwordTarget = ref(null)
const passwordForm = reactive({ password: '', confirmation: '' })

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
      ? `Xóa ${selectedCount.value} nhân viên đã chọn`
      : 'Chọn nhân viên để xóa',
  },
])

const dialogTitle = computed(() => (editingId.value ? 'Sửa nhân viên' : 'Thêm nhân viên'))

const rules = {
  name: [{ required: true, message: 'Nhập họ tên', trigger: 'blur' }],
  email: [
    { required: true, message: 'Nhập email', trigger: 'blur' },
    { type: 'email', message: 'Email không hợp lệ', trigger: 'blur' },
  ],
  password: [
    {
      validator: (_rule, value, callback) => {
        if (!value) {
          if (editingId.value) callback()
          else callback(new Error('Nhập mật khẩu'))
          return
        }
        if (value.length < 8) callback(new Error('Mật khẩu tối thiểu 8 ký tự'))
        else callback()
      },
      trigger: 'blur',
    },
  ],
  confirmation: [
    {
      validator: (_rule, value, callback) => {
        if (editingId.value) {
          callback()
          return
        }
        if (!value) callback(new Error('Nhập lại mật khẩu'))
        else if (value !== form.password) callback(new Error('Mật khẩu nhập lại chưa khớp'))
        else callback()
      },
      trigger: 'blur',
    },
  ],
  role: [{ required: true, message: 'Chọn vai trò', trigger: 'change' }],
  trang_thai: [{ required: true, message: 'Chọn trạng thái', trigger: 'change' }],
}

const passwordRules = {
  password: [
    { required: true, message: 'Nhập mật khẩu mới', trigger: 'blur' },
    { min: 8, message: 'Mật khẩu tối thiểu 8 ký tự', trigger: 'blur' },
  ],
  confirmation: [
    { required: true, message: 'Nhập lại mật khẩu', trigger: 'blur' },
    {
      validator: (_rule, value, callback) => {
        if (value !== passwordForm.password) callback(new Error('Mật khẩu nhập lại chưa khớp'))
        else callback()
      },
      trigger: 'blur',
    },
  ],
}

const roleMeta = (value) => roles.find((item) => item.value === value) || roles[3]

const formatDate = (value) =>
  value
    ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
    : ''

function errorMessage(error) {
  const errors = error.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return error.response?.data?.message || 'Không thể thực hiện thao tác.'
}

function onSelectionChange(rows) {
  selectedRows.value = rows || []
}

function searchUsers() {
  page.value = 1
  loadUsers()
}

async function loadUsers() {
  loading.value = true
  selectedRows.value = []
  tableRef.value?.clearSelection?.()
  try {
    const { data } = await http.get('/users', {
      params: {
        q: keyword.value.trim() || undefined,
        role: roleFilter.value || undefined,
        start: (page.value - 1) * limit.value,
        limit: limit.value,
      },
    })
    total.value = Number(data.total) || 0
    const lastPage = Math.max(1, Math.ceil(total.value / limit.value) || 1)
    if (page.value > lastPage) {
      page.value = lastPage
      return loadUsers()
    }
    users.value = data.data
  } catch (error) {
    ElMessage.error(errorMessage(error))
  } finally {
    loading.value = false
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    name: '',
    email: '',
    password: '',
    confirmation: '',
    role: 'user',
    trang_thai: 'hoat_dong',
  })
}

function onDialogClosed() {
  resetForm()
  if (!shouldReload.value) return
  shouldReload.value = false
  if (resetPageOnReload.value) {
    resetPageOnReload.value = false
    page.value = 1
  }
  loadUsers()
}

function openCreate() {
  resetForm()
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(form, {
    name: row.name,
    email: row.email,
    password: '',
    confirmation: '',
    role: row.role,
    trang_thai: row.trang_thai || 'hoat_dong',
  })
  dialogOpen.value = true
  nextTick(() => formRef.value?.clearValidate())
}

function openPassword(row) {
  passwordTarget.value = row
  Object.assign(passwordForm, { password: '', confirmation: '' })
  passwordOpen.value = true
  nextTick(() => passwordFormRef.value?.clearValidate())
}

async function saveUser() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  const payload = {
    name: form.name.trim(),
    email: form.email.trim(),
    role: form.role,
    trang_thai: form.trang_thai,
  }
  if (form.password) payload.password = form.password

  try {
    const { data } = editingId.value
      ? await http.put(`/users/${editingId.value}`, payload)
      : await http.post('/users', payload)
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

async function savePassword() {
  const valid = await passwordFormRef.value?.validate().catch(() => false)
  if (!valid || !passwordTarget.value) return

  passwordSaving.value = true
  const row = passwordTarget.value
  try {
    const { data } = await http.put(`/users/${row.id}`, {
      name: row.name,
      email: row.email,
      role: row.role,
      trang_thai: row.trang_thai || 'hoat_dong',
      password: passwordForm.password,
    })
    ElMessage.success(data.message || 'Đã đổi mật khẩu.')
    passwordOpen.value = false
  } catch (error) {
    ElMessage.error(errorMessage(error))
  } finally {
    passwordSaving.value = false
  }
}

async function removeUsers(rows) {
  const targets = [...rows]
  if (!targets.length) return
  const label = targets.length === 1 ? `tài khoản của ${targets[0].name}` : `${targets.length} nhân viên đã chọn`
  try {
    await ElMessageBox.confirm(`Xóa ${label}?`, 'Xóa nhân viên', {
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
      await http.delete(`/users/${row.id}`)
    }
    ElMessage.success(targets.length === 1 ? 'Đã xóa nhân viên.' : `Đã xóa ${targets.length} nhân viên.`)
    await loadUsers()
  } catch (error) {
    ElMessage.error(errorMessage(error))
    await loadUsers()
  } finally {
    bulkDeleting.value = false
  }
}

function onBulkAction(key) {
  if (key === 'delete') removeUsers(selectedRows.value)
}

function removeUser(row) {
  return removeUsers([row])
}

async function updateStatus(row, value) {
  if (!row?.id || statusUpdatingId.value === row.id || row.trang_thai === value) return
  const previous = row.trang_thai
  row.trang_thai = value
  statusUpdatingId.value = row.id
  try {
    const { data } = await http.put(`/users/${row.id}`, {
      name: row.name,
      email: row.email,
      role: row.role,
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

onMounted(loadUsers)
</script>

<template>
  <div class="page-list">
    <CustomCard shadow="never" class="filter-card">
      <div class="filters">
        <CustomInput
          v-model="keyword"
          clearable
          placeholder="Tìm theo tên hoặc email..."
          @keyup.enter="searchUsers"
          @clear="searchUsers"
        >
          <template #prefix><CustomIcon><Search /></CustomIcon></template>
        </CustomInput>
        <CustomSelect v-model="roleFilter" clearable placeholder="Tất cả vai trò">
          <CustomOption v-for="item in roles" :key="item.value" :label="item.label" :value="item.value" />
        </CustomSelect>
        <CustomButton type="primary" @click="searchUsers">Tìm kiếm</CustomButton>
      </div>
    </CustomCard>

    <CustomCard shadow="never" class="table-card">
      <template #header>
        <div class="card-header">
          <span class="card-title">Danh sách nhân viên</span>
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
        :data="users"
        row-key="id"
        stripe
        @selection-change="onSelectionChange"
      >
        <CustomTableColumn type="selection" width="48" align="center" />
        <CustomTableColumn label="STT" width="70" align="center">
          <template #default="{ $index }">{{ (page - 1) * limit + $index + 1 }}</template>
        </CustomTableColumn>
        <CustomTableColumn prop="name" label="Tên nhân viên" min-width="180" />
        <CustomTableColumn prop="email" label="Email" min-width="220" />
        <CustomTableColumn label="Vai trò" width="150">
          <template #default="{ row }">
            <CustomTag :type="roleMeta(row.role).type" effect="light">{{ roleMeta(row.role).label }}</CustomTag>
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Trạng thái" width="150" align="center">
          <template #default="{ row }">
            <CustomSwitch
              v-if="row.id"
              :model-value="row.trang_thai"
              active-value="hoat_dong"
              inactive-value="ngung_hoat_dong"
              active-text="Hoạt động"
              inactive-text="Ngừng"
              :loading="statusUpdatingId === row.id"
              :disabled="statusUpdatingId === row.id"
              @change="(value) => updateStatus(row, value)"
            />
          </template>
        </CustomTableColumn>
        <CustomTableColumn label="Ngày tạo" width="170">
          <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
        </CustomTableColumn>
        <CustomTableColumn label="Thao tác" width="150" align="center" fixed="right">
          <template #default="{ row }">
            <div class="action-btns">
              <CustomTooltip content="Sửa" placement="top">
                <CustomButton text circle @click="openEdit(row)"><CustomIcon><Edit /></CustomIcon></CustomButton>
              </CustomTooltip>
              <CustomTooltip content="Đổi mật khẩu" placement="top">
                <CustomButton text circle @click="openPassword(row)"><CustomIcon><Key /></CustomIcon></CustomButton>
              </CustomTooltip>
              <CustomTooltip content="Xóa" placement="top">
                <CustomButton text circle type="danger" @click="removeUser(row)"><CustomIcon><Delete /></CustomIcon></CustomButton>
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
        @change="loadUsers"
      />
    </CustomCard>

    <CustomDialog v-model="dialogOpen" :title="dialogTitle" :width="640" @closed="onDialogClosed">
      <CustomForm ref="formRef" :model="form" :rules="rules">
        <CustomRow :gutter="14">
          <CustomCol :span="12">
            <CustomFormItem label="Họ tên" prop="name">
              <CustomInput v-model="form.name" placeholder="Nguyễn Văn A" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="12">
            <CustomFormItem label="Email" prop="email">
              <CustomInput v-model="form.email" placeholder="ten@duongtrungson.vn" />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-if="!editingId" :span="12">
            <CustomFormItem label="Mật khẩu" prop="password">
              <CustomInput
                v-model="form.password"
                type="password"
                show-password
                placeholder="Tối thiểu 8 ký tự"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol v-if="!editingId" :span="12">
            <CustomFormItem label="Nhập lại mật khẩu" prop="confirmation">
              <CustomInput
                v-model="form.confirmation"
                type="password"
                show-password
                placeholder="Nhập lại mật khẩu"
              />
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="12">
            <CustomFormItem label="Vai trò" prop="role">
              <CustomSelect v-model="form.role" class="role-select" :clearable="false">
                <CustomOption v-for="item in roles" :key="item.value" :label="item.label" :value="item.value" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
          <CustomCol :span="12">
            <CustomFormItem label="Trạng thái" prop="trang_thai">
              <CustomSelect v-model="form.trang_thai" class="role-select" :clearable="false">
                <CustomOption v-for="item in statuses" :key="item.value" :label="item.label" :value="item.value" />
              </CustomSelect>
            </CustomFormItem>
          </CustomCol>
        </CustomRow>
      </CustomForm>
      <template #footer>
        <CustomButton @click="dialogOpen = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="saving" @click="saveUser">Lưu</CustomButton>
      </template>
    </CustomDialog>

    <CustomDialog v-model="passwordOpen" title="Đổi mật khẩu" :width="420">
      <CustomForm ref="passwordFormRef" :model="passwordForm" :rules="passwordRules">
        <CustomFormItem label="Mật khẩu mới" prop="password">
          <CustomInput
            v-model="passwordForm.password"
            type="password"
            show-password
            placeholder="Tối thiểu 8 ký tự"
          />
        </CustomFormItem>
        <CustomFormItem label="Nhập lại mật khẩu" prop="confirmation">
          <CustomInput
            v-model="passwordForm.confirmation"
            type="password"
            show-password
            placeholder="Nhập lại mật khẩu mới"
          />
        </CustomFormItem>
      </CustomForm>
      <template #footer>
        <CustomButton @click="passwordOpen = false">Hủy</CustomButton>
        <CustomButton type="primary" :loading="passwordSaving" @click="savePassword">Lưu mật khẩu</CustomButton>
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
  :deep(.el-select) { width: 180px; }
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

.role-select {
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
