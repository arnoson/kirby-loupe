<template>
  <div class="loupe-reindex-field" :data-state="store.state">
    <k-button
      variant="filled"
      :icon="isLoading ? 'loader' : null"
      :disabled="isLoading"
      @click="reindex"
      >{{ t("arnoson.kirby-loupe.reindex-site") }}</k-button
    >
    <k-progress v-if="store.state === 'index-chunks'" :value="store.progress" />
    <k-box
      v-if="store.state === 'index-chunks'"
      theme="info"
      :text="t('arnoson.kirby-loupe.info-chunk-reindex')"
    />
    <k-box
      v-if="store.state === 'success'"
      theme="positive"
      :text="`${t('arnoson.kirby-loupe.success', { count: store.count })}`"
    />
    <k-box
      v-if="store.state === 'error'"
      theme="negative"
      :text="`${t('arnoson.kirby-loupe.error')}: ${store.error.message}`"
    />
  </div>
</template>

<script setup lang="ts">
import { useApi } from "kirbyuse"
import { computed, onMounted } from "vue"
import store from "./store"

const props = withDefaults(defineProps<{ chunk: boolean | number }>(), {
  chunk: true,
})
const api = useApi()
const { t } = window.panel

const isLoading = computed(
  () => store.state === "index" || store.state === "index-chunks",
)

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
  if (store.state === "index-chunks") e.preventDefault()
}
onMounted(() => {
  window.addEventListener("beforeunload", handleBeforeUnload)
  if (store.state !== "index-chunks") store.state = "idle"
})

const reindex = () => {
  store.error = null
  store.progress = 0
  if (props.chunk) reindexChunked()
  else reindexAll()
}

const reindexAll = async () => {
  store.state = "index"
  try {
    const result = await api.get("plugin-kirby-loupe/reindex-all")
    store.count = result.count
    store.state = "success"
  } catch (e) {
    store.error = e
    store.state = "error"
  }
}

const reindexChunked = async () => {
  store.state = "index-chunks"
  store.progress = 1 // Show immediate feedback that process started

  const uuids = await api.get("/plugin-kirby-loupe/reindex-chunk/start")
  store.count = uuids.length

  const chunkSize = typeof props.chunk === "number" ? props.chunk : 100
  const chunksCount = Math.ceil(uuids.length / chunkSize)

  for (let i = 0; i < chunksCount; i++) {
    const chunk = uuids.slice(i * chunkSize, (i + 1) * chunkSize)
    try {
      await api.post("/plugin-kirby-loupe/reindex-chunk", { uuids: chunk })
    } catch (e) {
      store.error = e
      break
    }
    store.progress = 1 + (i / (chunksCount - 1)) * 99 // Progress started at 1.
  }

  store.state = store.error ? "error" : "success"
  window.removeEventListener("beforeunload", handleBeforeUnload)
}
</script>

<style scoped>
.loupe-reindex-field {
  display: flex;
  flex-direction: column;
  align-items: start;
  gap: var(--spacing-2);
}

.loupe-reindex-field[data-state="indexing"] button {
  cursor: wait;
}
</style>
