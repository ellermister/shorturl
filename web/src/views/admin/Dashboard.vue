<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'

const stats = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    stats.value = await api.adminStats(14)
  } catch (e) {
    error.value = e.message
  }
})
</script>

<template>
  <div class="dash">
    <h1>数据概览</h1>
    <p v-if="error" class="err">{{ error }}</p>

    <div v-if="stats" class="cards">
      <div class="metric">
        <span>今日 PV</span>
        <strong>{{ stats.today?.pv || 0 }}</strong>
      </div>
      <div class="metric">
        <span>今日 UV</span>
        <strong>{{ stats.today?.uv || 0 }}</strong>
      </div>
      <div class="metric">
        <span>今日新建</span>
        <strong>{{ stats.today?.link_created || 0 }}</strong>
      </div>
      <div class="metric">
        <span>累计访问</span>
        <strong>{{ stats.visit_total || 0 }}</strong>
      </div>
      <div class="metric">
        <span>有效短链</span>
        <strong>{{ stats.link_active || 0 }}</strong>
      </div>
      <div class="metric">
        <span>历史总数</span>
        <strong>{{ stats.link_total || 0 }}</strong>
      </div>
    </div>

    <div v-if="stats?.days?.length" class="table-wrap">
      <h2>近 14 日</h2>
      <table>
        <thead>
          <tr>
            <th>日期</th>
            <th>PV</th>
            <th>UV</th>
            <th>新建</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in stats.days" :key="d.stat_date">
            <td>{{ d.stat_date }}</td>
            <td>{{ d.pv }}</td>
            <td>{{ d.uv }}</td>
            <td>{{ d.link_created }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.dash h1,
.dash h2 {
  margin: 0 0 0.85rem;
  font-family: Syne, Manrope, sans-serif;
  color: var(--ink);
}
.dash h2 {
  font-size: 1.05rem;
  margin-top: 1.5rem;
}
.err {
  color: var(--danger);
  font-weight: 600;
}
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.75rem;
}
.metric {
  background: #fff;
  border: 1px solid var(--line);
  padding: 0.9rem;
  display: grid;
  gap: 0.35rem;
}
.metric span {
  color: var(--ink-soft);
  font-size: 0.82rem;
  font-weight: 600;
}
.metric strong {
  color: var(--ink);
  font-size: 1.55rem;
  font-weight: 800;
  line-height: 1;
}
.table-wrap {
  margin-top: 0.5rem;
  overflow-x: auto;
  background: #fff;
  border: 1px solid var(--line);
  padding: 0.75rem;
}
table {
  width: 100%;
  border-collapse: collapse;
  color: var(--ink);
}
th,
td {
  text-align: left;
  padding: 0.55rem 0.4rem;
  border-bottom: 1px solid var(--line);
  font-size: 0.92rem;
}
th {
  color: var(--ink-soft);
  font-weight: 700;
}
</style>
