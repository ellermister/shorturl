<script setup>
defineProps({
  text: { type: String, required: true },
})
</script>

<template>
  <span class="tip" tabindex="0" :aria-label="text" @click.stop.prevent>
    <span class="tip-mark" aria-hidden="true">?</span>
    <span class="tip-bubble" role="tooltip">{{ text }}</span>
  </span>
</template>

<style scoped>
.tip {
  position: relative;
  display: inline-flex;
  align-items: center;
  margin-left: 0.2rem;
  vertical-align: middle;
}
.tip-mark {
  width: 1rem;
  height: 1rem;
  border-radius: 999px;
  border: 1px solid var(--line);
  color: var(--ink-soft);
  font-size: 0.65rem;
  font-weight: 700;
  line-height: 1;
  display: inline-grid;
  place-items: center;
  background: var(--paper-2);
  cursor: help;
}
.tip-bubble {
  position: absolute;
  left: 50%;
  bottom: calc(100% + 0.45rem);
  transform: translateX(-50%) translateY(4px);
  width: max-content;
  max-width: min(240px, 70vw);
  padding: 0.45rem 0.6rem;
  border: 1px solid var(--line);
  background: var(--ink);
  color: #f4f7f9;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.4;
  white-space: normal;
  text-align: left;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.15s ease, transform 0.15s ease;
  z-index: 40;
}
.tip-bubble::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  margin-left: -5px;
  border: 5px solid transparent;
  border-top-color: var(--ink);
}
.tip:hover .tip-bubble,
.tip:focus-visible .tip-bubble {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
</style>
