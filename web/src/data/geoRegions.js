/** Static region labels for geo-routing UI (not loaded from xdb). */

/** ISO-3166 alpha-2 → display names. Value stored/matched is the code. */
export const COUNTRIES = [
  { code: 'CN', names: { 'zh-CN': '中国', en: 'China', ja: '中国' } },
  { code: 'JP', names: { 'zh-CN': '日本', en: 'Japan', ja: '日本' } },
  { code: 'KR', names: { 'zh-CN': '韩国', en: 'South Korea', ja: '韓国' } },
  { code: 'US', names: { 'zh-CN': '美国', en: 'United States', ja: 'アメリカ' } },
  { code: 'GB', names: { 'zh-CN': '英国', en: 'United Kingdom', ja: 'イギリス' } },
  { code: 'DE', names: { 'zh-CN': '德国', en: 'Germany', ja: 'ドイツ' } },
  { code: 'FR', names: { 'zh-CN': '法国', en: 'France', ja: 'フランス' } },
  { code: 'AU', names: { 'zh-CN': '澳大利亚', en: 'Australia', ja: 'オーストラリア' } },
  { code: 'CA', names: { 'zh-CN': '加拿大', en: 'Canada', ja: 'カナダ' } },
  { code: 'SG', names: { 'zh-CN': '新加坡', en: 'Singapore', ja: 'シンガポール' } },
  { code: 'MY', names: { 'zh-CN': '马来西亚', en: 'Malaysia', ja: 'マレーシア' } },
  { code: 'TH', names: { 'zh-CN': '泰国', en: 'Thailand', ja: 'タイ' } },
  { code: 'VN', names: { 'zh-CN': '越南', en: 'Vietnam', ja: 'ベトナム' } },
  { code: 'ID', names: { 'zh-CN': '印度尼西亚', en: 'Indonesia', ja: 'インドネシア' } },
  { code: 'IN', names: { 'zh-CN': '印度', en: 'India', ja: 'インド' } },
  { code: 'RU', names: { 'zh-CN': '俄罗斯', en: 'Russia', ja: 'ロシア' } },
  { code: 'BR', names: { 'zh-CN': '巴西', en: 'Brazil', ja: 'ブラジル' } },
  { code: 'TW', names: { 'zh-CN': '中国台湾', en: 'Taiwan', ja: '台湾' } },
  { code: 'HK', names: { 'zh-CN': '中国香港', en: 'Hong Kong', ja: '香港' } },
  { code: 'MO', names: { 'zh-CN': '中国澳门', en: 'Macao', ja: 'マカオ' } },
]

/** Mainland + common admin divisions for CN province dropdown. */
export const CN_PROVINCES = [
  '北京', '天津', '上海', '重庆',
  '河北', '山西', '辽宁', '吉林', '黑龙江',
  '江苏', '浙江', '安徽', '福建', '江西', '山东',
  '河南', '湖北', '湖南', '广东', '海南',
  '四川', '贵州', '云南', '陕西', '甘肃', '青海',
  '台湾',
  '内蒙古', '广西', '西藏', '宁夏', '新疆',
  '香港', '澳门',
]

export const ISP_OPTIONS = [
  { value: '', labelKey: 'any' },
  { value: 'telecom', labelKey: 'telecom' },
  { value: 'unicom', labelKey: 'unicom' },
  { value: 'mobile', labelKey: 'mobile' },
  { value: 'other', labelKey: 'other' },
]

export function emptyGeoRule() {
  return { country: '', province: '', city: '', isp: '', url: '' }
}

export function countryLabel(c, locale) {
  return c.names[locale] || c.names.en || c.code
}
