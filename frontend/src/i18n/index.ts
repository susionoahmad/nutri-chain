import { createI18n } from 'vue-i18n';
import id from './id.json';
import en from './en.json';

const i18n = createI18n({
  legacy: false,
  locale: localStorage.getItem('locale') || 'id',
  fallbackLocale: 'en',
  messages: {
    id,
    en
  }
});

export default i18n;
