import { defineConfig } from "kirbyup/config"

export default defineConfig({
  vite: {
    server: { cors: { origin: "https://kirby-loupe.ddev.site" } },
  },
})
