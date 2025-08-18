try {
  await import("http://127.0.0.1:5177/src/index.ts");
} catch (err) {
  console.error(
    "[kirbyup] Couldn't connect to the development server. Run `npm run serve` to start Vite or build the plugin with `npm run build` so Kirby uses the production version."
  );
  throw err;
}
