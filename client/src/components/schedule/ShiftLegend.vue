<template>
  <div class="shift-legend" :class="{ compact }">
    <div v-if="!compact" class="legend-title">Shift Legend</div>
    
    <div class="legend-items" :class="{ 'legend-compact': compact }">
      <div 
        v-for="legend in filteredLegends" 
        :key="`${legend.code}-${legend.department}`"
        class="legend-item"
        :title="legend.timeRange"
      >
        <!-- Single color indicator -->
        <div 
          v-if="!legend.colorSecondary"
          class="color-indicator"
          :style="{ backgroundColor: legend.colorPrimary }"
          :class="{ 'off-duty': legend.code === 'OFF' }"
        ></div>
        
        <!-- Multi-color indicator (split shifts) -->
        <div 
          v-else
          class="color-indicator multi-color"
        >
          <div 
            class="color-half left"
            :style="{ backgroundColor: legend.colorPrimary }"
          ></div>
          <div 
            class="color-half right"
            :style="{ backgroundColor: legend.colorSecondary }"
          ></div>
        </div>
        
        <!-- Legend text -->
        <div class="legend-text">
          <span class="legend-code">{{ legend.code }}</span>
          <span v-if="!compact" class="legend-time">{{ legend.timeRange }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  department: {
    type: String,
    default: null
  },
  legends: {
    type: Array,
    required: true
  },
  compact: {
    type: Boolean,
    default: false
  }
})

// Filter legends based on department
const filteredLegends = computed(() => {
  if (!props.department) {
    // Show only standard legends (department = null)
    return props.legends
      .filter(l => l.department === null)
      .sort((a, b) => a.displayOrder - b.displayOrder)
  }
  
  // Show department-specific legends
  const deptLegends = props.legends
    .filter(l => l.department === props.department)
    .sort((a, b) => a.displayOrder - b.displayOrder)
  
  // If no department-specific legends, fall back to standard
  if (deptLegends.length === 0) {
    return props.legends
      .filter(l => l.department === null)
      .sort((a, b) => a.displayOrder - b.displayOrder)
  }
  
  return deptLegends
})

// Check if any legend has multi-color
const hasMultiColor = computed(() => {
  return filteredLegends.value.some(l => l.colorSecondary !== null)
})
</script>

<style scoped>
.shift-legend {
  padding: 12px;
  background: #f5f5f5;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.shift-legend.compact {
  padding: 8px;
  background: transparent;
  border: none;
}

.legend-title {
  font-weight: 600;
  margin-bottom: 12px;
  color: #333;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.legend-items {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.legend-items.legend-compact {
  gap: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  background: white;
  border-radius: 4px;
  border: 1px solid #e0e0e0;
  transition: all 0.2s;
  cursor: help;
}

.compact .legend-item {
  padding: 4px 8px;
  border: none;
  background: transparent;
}

.legend-item:hover {
  border-color: #999;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.compact .legend-item:hover {
  box-shadow: none;
  background: rgba(0,0,0,0.05);
}

.color-indicator {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  flex-shrink: 0;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
}

.compact .color-indicator {
  width: 16px;
  height: 16px;
}

.color-indicator.off-duty {
  background: transparent !important;
  border: 2px solid #F44336;
  box-shadow: none;
}

.color-indicator.multi-color {
  display: flex;
  overflow: hidden;
  padding: 0;
  border: 1px solid rgba(0,0,0,0.1);
}

.color-half {
  width: 50%;
  height: 100%;
}

.color-half.left {
  border-right: 1px solid rgba(255,255,255,0.3);
}

.legend-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.compact .legend-text {
  flex-direction: row;
  gap: 4px;
  align-items: center;
}

.legend-code {
  font-weight: 600;
  color: #333;
  font-size: 0.875rem;
}

.compact .legend-code {
  font-size: 0.75rem;
}

.legend-time {
  font-size: 0.75rem;
  color: #666;
}

/* Print styles */
@media print {
  .shift-legend {
    background: white;
    border: 1px solid #000;
    page-break-inside: avoid;
  }
  
  .legend-item {
    border: 1px solid #000;
  }
  
  .color-indicator {
    border: 1px solid #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
}
</style>
