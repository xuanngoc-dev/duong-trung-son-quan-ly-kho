<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import * as Icons from '@element-plus/icons-vue'
import menuGroups from '@/data/menu.json'

defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})

const route = useRoute()
const activeMenu = computed(() => route.path)
const resolveIcon = (name) => Icons[name] || Icons.Menu
</script>

<template>
  <el-menu
    :default-active="activeMenu"
    :collapse="collapsed"
    :collapse-transition="false"
    router
    class="side-menu"
  >
    <div v-for="(group, index) in menuGroups" :key="group.header || index" class="menu-group">
      <div v-if="group.header" class="group-title">
        <span class="group-abbr">{{ group.abbr }}</span>
        <span class="group-label">{{ group.header }}</span>
      </div>

      <el-menu-item v-for="item in group.items" :key="item.index" :index="item.index">
        <el-icon><component :is="resolveIcon(item.icon)" /></el-icon>
        <template #title>{{ item.title }}</template>
      </el-menu-item>
    </div>
  </el-menu>
</template>

<style scoped lang="scss">
.side-menu {
  width: 100%;
  border-right: 0;

  :deep(.el-menu-item) {
    height: 36px;
    line-height: 36px;
    margin: 0 8px;
    border-radius: 8px;
  }

  &.el-menu--collapse {
    width: 64px;

    :deep(.el-menu-item) {
      justify-content: center;
      margin-inline: 6px;
      padding: 0 !important;
    }
  }
}

.menu-group + .menu-group {
  margin-top: 2px;
}

.group-title {
  min-height: 26px;
  padding: 8px 20px 2px;
  color: var(--el-text-color-secondary);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  white-space: nowrap;
}

.group-abbr {
  display: none;
}

.el-menu--collapse {
  .group-title {
    padding-inline: 0;
    text-align: center;
  }

  .group-label {
    display: none;
  }

  .group-abbr {
    display: inline;
  }
}
</style>
