import { defineConfig } from 'kirby-deploy'

// Demo Site

export default defineConfig({
  host: process.env.FTP_HOST,
  user: process.env.FTP_USER,
  password: process.env.FTP_PASSWORD,
  callWebhooks: false,
  checkComposerLock: false,
  lftpFlags: ['--no-perms'],
  folderStructure: {
    content: 'example/content',
    media: 'example/public/media',
    accounts: 'example/site/accounts',
    sessions: 'example/site/sessions',
    cache: 'example/site/cache',
    site: 'example/site',
  },
})