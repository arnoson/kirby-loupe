import { reactive } from "kirbyuse"

export default reactive({
  state: "idle" as "idle" | "index" | "index-chunks" | "success" | "error",
  count: 0,
  progress: 0,
  error: null as any,
})
