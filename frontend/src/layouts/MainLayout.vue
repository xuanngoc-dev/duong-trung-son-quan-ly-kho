<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import SideMenu from '@/components/SideMenu.vue'
import api from '@/api/http'
import { useLayoutStore } from '@/stores/layout'

const MOBILE_BREAKPOINT = 992
const route = useRoute()
const layoutStore = useLayoutStore()
const { sidebarCollapsed, darkMode } = storeToRefs(layoutStore)

const isMobile = ref(false)
const mobileMenuOpen = ref(false)
const settingsOpen = ref(false)
const apiOnline = ref(false)
const now = ref(new Date())

const pageTitle = computed(() => route.meta.title || 'Quản lý kho')
const sidebarWidth = computed(() => (sidebarCollapsed.value ? '64px' : '220px'))
const dateLabel = computed(() =>
  now.value.toLocaleString('vi-VN', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }),
)

let mediaQuery
let clock

function syncViewport(event) {
  isMobile.value = event.matches
  mobileMenuOpen.value = false
}

function toggleMenu() {
  if (isMobile.value) {
    mobileMenuOpen.value = !mobileMenuOpen.value
  } else {
    layoutStore.toggleSidebar()
  }
}

async function checkApi() {
  try {
    await api.get('/status')
    apiOnline.value = true
  } catch {
    apiOnline.value = false
  }
}

async function logout() {
  try {
    await ElMessageBox.confirm('Đăng xuất khỏi hệ thống?', 'Đăng xuất', {
      type: 'warning',
      confirmButtonText: 'Đăng xuất',
      cancelButtonText: 'Hủy',
    })
  } catch {
    return
  }

  try {
    if (localStorage.getItem('auth_token')) {
      await api.post('/logout')
    }
  } catch {
    // Phiên đã hết hạn vẫn được xóa trên trình duyệt.
  }

  localStorage.removeItem('auth_token')
  ElMessage.success('Đã đăng xuất.')
}

function onUserCommand(command) {
  if (command === 'logout') logout()
}

watch(() => route.fullPath, () => {
  mobileMenuOpen.value = false
})

onMounted(() => {
  mediaQuery = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`)
  syncViewport(mediaQuery)
  mediaQuery.addEventListener('change', syncViewport)
  clock = window.setInterval(() => (now.value = new Date()), 60000)
  checkApi()
})

onUnmounted(() => {
  mediaQuery?.removeEventListener('change', syncViewport)
  window.clearInterval(clock)
})
</script>

<template>
  <div class="app-shell">
    <div v-if="mobileMenuOpen" class="sidebar-mask" @click="mobileMenuOpen = false" />

    <aside
      class="sidebar"
      :class="{ 'is-mobile': isMobile, 'is-open': mobileMenuOpen }"
      :style="{ width: isMobile ? '220px' : sidebarWidth }"
    >
      <div class="brand">
        <div class="brand-mark"><el-icon><Box /></el-icon></div>
        <div v-show="!sidebarCollapsed || isMobile" class="brand-copy">
          <strong>Quản lý kho</strong>
          <small>Quản lý kho</small>
        </div>
      </div>
      <div class="sidebar-scroll">
        <SideMenu :collapsed="sidebarCollapsed && !isMobile" />
      </div>
      <div v-show="!sidebarCollapsed || isMobile" class="sidebar-footer">
        <span class="status-dot" :class="{ online: apiOnline }" />
        {{ apiOnline ? 'API đang hoạt động' : 'API chưa kết nối' }}
      </div>
    </aside>

    <section class="content" :style="{ marginLeft: isMobile ? 0 : sidebarWidth }">
      <header class="topbar">
        <div class="topbar-left">
          <el-button text circle @click="toggleMenu">
            <el-icon :size="21">
              <Fold v-if="mobileMenuOpen || (!isMobile && !sidebarCollapsed)" />
              <Expand v-else />
            </el-icon>
          </el-button>
          <div>
            <!-- <h1>{{ pageTitle }}</h1>
            <el-breadcrumb separator="/">
              <el-breadcrumb-item>Quản lý kho</el-breadcrumb-item>
              <el-breadcrumb-item>{{ pageTitle }}</el-breadcrumb-item>
            </el-breadcrumb> -->
          </div>
        </div>

        <div class="topbar-right">
          <button class="quick-search" type="button">
            <el-icon><Search /></el-icon>
            <span>Tìm kiếm nhanh...</span>
            <kbd>⌘K</kbd>
          </button>
          <span class="clock">{{ dateLabel }}</span>
          <el-switch
            :model-value="darkMode"
            inline-prompt
            active-text="🌙"
            inactive-text="☀️"
            @change="layoutStore.setDarkMode"
          />
          <el-button text circle @click="settingsOpen = true">
            <el-icon :size="20"><Setting /></el-icon>
          </el-button>
          <el-dropdown @command="onUserCommand">
            <div class="user">
              <el-avatar :size="34">A</el-avatar>
              <div class="user-copy">
                <strong>Quản trị viên</strong>
                <small>Admin</small>
              </div>
              <el-icon><ArrowDown /></el-icon>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item>Thông tin tài khoản</el-dropdown-item>
                <el-dropdown-item command="logout" divided>Đăng xuất</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>

      <main class="main-content">
        <router-view />
      </main>
    </section>

    <el-drawer v-model="settingsOpen" title="Cài đặt giao diện" size="320px">
      <div class="setting-row">
        <span>Giao diện tối</span>
        <el-switch :model-value="darkMode" @change="layoutStore.setDarkMode" />
      </div>
      <div class="setting-row">
        <span>Thu gọn menu</span>
        <el-switch v-model="sidebarCollapsed" />
      </div>
    </el-drawer>
  </div>
</template>

<style scoped lang="scss">
.app-shell {
  min-height: 100vh;
}

.sidebar {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 50;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-right: 1px solid var(--el-border-color);
  background: var(--el-bg-color);
  transition: width 0.25s ease, transform 0.25s ease;

  &.is-mobile {
    z-index: 101;
    transform: translateX(-100%);
  }

  &.is-mobile.is-open {
    transform: translateX(0);
    box-shadow: var(--el-box-shadow-dark);
  }
}

.sidebar-mask {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(0, 0, 0, 0.42);
}

.brand {
  display: flex;
  height: 64px;
  flex-shrink: 0;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
  border-bottom: 1px solid var(--el-border-color);
  color: var(--el-color-primary);
  white-space: nowrap;
}

.brand-mark {
  display: grid;
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 10px;
  color: #fff;
  background: linear-gradient(145deg, #337ecc, #409eff);
}

.brand-copy,
.user-copy {
  display: flex;
  min-width: 0;
  flex-direction: column;

  small {
    color: var(--el-text-color-secondary);
    font-size: 11px;
  }
}

.sidebar-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.sidebar-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 18px;
  border-top: 1px solid var(--el-border-color);
  color: var(--el-text-color-secondary);
  font-size: 12px;
  white-space: nowrap;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--el-color-danger);

  &.online {
    background: var(--el-color-success);
  }
}

.content {
  min-width: 0;
  transition: margin-left 0.25s ease;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 40;
  display: flex;
  height: 64px;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 0 20px;
  border-bottom: 1px solid var(--el-border-color);
  background: color-mix(in srgb, var(--el-bg-color) 92%, transparent);
  backdrop-filter: blur(12px);
}

.topbar-left,
.topbar-right,
.user {
  display: flex;
  align-items: center;
}

.topbar-left {
  gap: 10px;

  h1 {
    margin: 0 0 4px;
    font-size: 18px;
  }
}

.topbar-right {
  gap: 12px;
}

.quick-search {
  display: flex;
  width: 230px;
  align-items: center;
  gap: 8px;
  padding: 7px 9px;
  border: 1px solid var(--el-border-color);
  border-radius: 8px;
  color: var(--el-text-color-secondary);
  background: var(--el-fill-color-light);

  span {
    flex: 1;
    text-align: left;
  }

  kbd {
    padding: 1px 5px;
    border: 1px solid var(--el-border-color);
    border-radius: 4px;
    background: var(--el-bg-color);
  }
}

.clock {
  color: var(--el-text-color-secondary);
  font-size: 12px;
  white-space: nowrap;
}

.user {
  gap: 8px;
  cursor: pointer;
}

.user-copy strong {
  font-size: 13px;
}

.main-content {
  min-height: calc(100vh - 64px);
  padding: 20px;
  background: var(--el-bg-color-page);
}

.setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

@media (max-width: 1180px) {
  .clock,
  .quick-search span,
  .quick-search kbd {
    display: none;
  }

  .quick-search {
    width: 36px;
    justify-content: center;
  }
}

@media (max-width: 700px) {
  .topbar {
    padding: 0 10px;
  }

  .topbar-left :deep(.el-breadcrumb),
  .user-copy,
  .quick-search {
    display: none;
  }

  .main-content {
    padding: 10px;
  }
}
</style>
