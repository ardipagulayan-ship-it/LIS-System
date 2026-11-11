<template>
  <canvas ref="ecgCanvas" class="ecg-canvas"></canvas>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const ecgCanvas = ref(null)
let ctx = null
let animationId = null
let waveData = []
let currentOpacity = 0.0
let fadeDirection = 1 // 1 for fading in, -1 for fading out
const fadeSpeed = 0.005 // Adjust for slower/faster fade
const speed = 2
const amplitude = 30
const baseY = 50

// Generate sample ECG waveform data
const generateEcgData = () => {
  const data = []
  // Baseline
  for (let i = 0; i < 50; i++) data.push(baseY)
  // QRS complex
  data.push(baseY - amplitude)
  data.push(baseY + amplitude * 1.5)
  data.push(baseY - amplitude * 1.2)
  data.push(baseY)
  // T wave
  data.push(baseY + amplitude * 0.5)
  data.push(baseY)
  // Baseline
  for (let i = 0; i < 80; i++) data.push(baseY)
  return data
}

const initCanvas = () => {
  const canvas = ecgCanvas.value
  if (!canvas) return

  // Set canvas to parent container dimensions
  const parent = canvas.parentElement
  canvas.width = parent.clientWidth
  canvas.height = parent.clientHeight

  ctx = canvas.getContext('2d')
  ctx.strokeStyle = '#ff6b6b'
  ctx.lineWidth = 2
  ctx.lineJoin = 'round'

  waveData = generateEcgData()
}

const animateWave = () => {
  if (!ctx) return

  const canvas = ecgCanvas.value
  // Update opacity for fading effect
  currentOpacity += fadeDirection * fadeSpeed
  if (currentOpacity > 1.0) {
    currentOpacity = 1.0
    fadeDirection = -1
  } else if (currentOpacity < 0.0) {
    currentOpacity = 0.0
    fadeDirection = 1
  }
  ctx.strokeStyle = `rgba(255, 107, 107, ${currentOpacity})`

  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Draw waveform
  ctx.beginPath()
  const singleWaveWidth = waveData.length * 3 // Width of one complete ECG wave
  const numRepetitions = Math.ceil(canvas.width / singleWaveWidth) + 1 // +1 to ensure full coverage

  for (let rep = 0; rep < numRepetitions; rep++) {
    const offsetX = rep * singleWaveWidth
    for (let i = 0; i < waveData.length; i++) {
      const x = offsetX + (i * 3)
      const y = waveData[i] + (canvas.height / 2 - baseY)
      
      if (i === 0 && rep === 0) ctx.moveTo(x, y)
      else ctx.lineTo(x, y)
    }
  }
  ctx.stroke()

  animationId = requestAnimationFrame(animateWave)
}

const handleResize = () => {
  if (animationId) cancelAnimationFrame(animationId)
  initCanvas()
  animateWave()
}

onMounted(() => {
  initCanvas()
  animateWave()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  if (animationId) cancelAnimationFrame(animationId)
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.ecg-canvas {
  width: 100%;
  height: 100%;
  display: block;
}
</style>