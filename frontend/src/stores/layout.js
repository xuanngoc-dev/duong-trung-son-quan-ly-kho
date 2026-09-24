import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

const STORAGE_KEY = 'qlk-layout'

export const useLayoutStore = defineStore('layout', () => {
  const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
  const sidebarCollapsed = ref(saved.sidebarCollapsed ?? false)
  const darkMode = ref(saved.darkMode ?? false)

  watch(
    [sidebarCollapsed, darkMode],
    () => {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          sidebarCollapsed: sidebarCollapsed.value,
          darkMode: darkMode.value,
        }),
      )
    },
    { immediate: true },
  )

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function setDarkMode(value) {
    darkMode.value = value
    document.documentElement.classList.toggle('dark', value)
  }

  setDarkMode(darkMode.value)

  return { sidebarCollapsed, darkMode, toggleSidebar, setDarkMode }
})
