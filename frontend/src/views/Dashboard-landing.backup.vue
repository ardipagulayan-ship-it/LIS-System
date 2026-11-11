<template>
  <div class="dashboard-new-design">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="lab-tag">Laboratory Intelligence Platform</div>
      <div class="hero-content">
        <div class="heart-icon">
          <!-- Placeholder for heart icon -->
          <img src="https://i.imgur.com/2Q4Q3Q3.png" alt="Heart Icon" />
        </div>
        <p class="subtitle">The Future of Laboratory Intelligence</p>

        <h1 class="main-title">
          Operating at the <span class="highlight">Speed of Life</span>
        </h1>
        <p class="description">
          Qyvilon transforms static results into living intelligence—ensuring every
          specimen results in a timely, accurate, and actionable diagnosis.
        </p>
        <div class="hero-buttons">
          <el-button type="primary" size="large" class="start-button">
            Start Your Transformation <i class="el-icon-arrow-right"></i>
          </el-button>
          <el-button type="text" size="large" class="demo-button">
            <i class="el-icon-video-play"></i> Watch Demo
          </el-button>
        </div>
        <div class="hero-metrics">
          <div class="metric-item">
            <span class="metric-value">99.9%</span>
            <span class="metric-label">Uptime SLA</span>
          </div>
          <div class="metric-item">
            <span class="metric-value">3x</span>
            <span class="metric-label">Faster Results</span>
          </div>
          <div class="metric-item">
            <span class="metric-value">500+</span>
            <span class="metric-label">Labs Worldwide</span>
          </div>
        </div>
      </div>
      <img src="https://via.placeholder.com/600x400?text=Lab+Dashboard+Image" alt="Lab Dashboard" class="lab-dashboard-image" />
    </section>

    <!-- Core Pillars Section -->
    <section class="pillars-section">
      <p class="section-subtitle">Core Pillars</p>
      <h2 class="section-title">Built for the Modern Lab</h2>
      <p class="section-description">
        Three foundational principles that drive every feature and decision in Qyvilon.
      </p>
      <div class="pillars-grid">
        <el-card shadow="hover" class="pillar-card">
          <div class="pillar-icon">⚡</div>
          <h3>The Velocity of Care</h3>
          <p>
            We believe a delay in data is a delay in healing. Qyvilon eliminates friction and accelerates
            workflow from intake to report.
          </p>
        </el-card>
        <el-card shadow="hover" class="pillar-card">
          <div class="pillar-icon">🔗</div>
          <h3>Fluid Connectivity</h3>
          <p>
            Like a nervous system, Qyvilon connects instruments, departments, and doctors without
            barriers, creating seamless information flow.
          </p>
        </el-card>
        <el-card shadow="hover" class="pillar-card">
          <div class="pillar-icon">🎯</div>
          <h3>Adaptive Precision</h3>
          <p>
            Healthcare changes daily. Qyvilon evolves with your lab, scaling effortlessly as sample volume
            and complexity grow.
          </p>
        </el-card>
      </div>
    </section>

    <!-- Powerful Capabilities Section -->
    <section class="capabilities-section">
      <p class="section-subtitle">Powerful Capabilities</p>
      <h2 class="section-title">Everything you need to transform your laboratory operations.</h2>
      <div class="capabilities-grid">
        <el-card shadow="hover" class="capability-card">
          <div class="capability-icon">📈</div>
          <h3>Real-Time Analytics</h3>
          <p>
            Monitor lab performance with live dashboards and instant alerts on critical results.
          </p>
        </el-card>
        <el-card shadow="hover" class="capability-card">
          <div class="capability-icon">🛡️</div>
          <h3>Compliance Ready</h3>
          <p>
            Built-in HIPAA, CAP, and CLIA compliance with automated audit trails.
          </p>
        </el-card>
        <el-card shadow="hover" class="capability-card">
          <div class="capability-icon">💡</div>
          <h3>Smart Insights</h3>
          <p>
            AI-powered pattern detection helps identify trends and optimize workflows.
          </p>
        </el-card>
      </div>
    </section>
  </div>
</template>

<script setup>
// No script logic needed for this static page yet.
// Will add animation logic here later.
</script>

<style scoped>
.dashboard-new-design {
  font-family: var(--font-family-base);
  color: var(--color-text-primary);
  line-height: var(--line-height-normal);
  background-color: var(--color-bg-secondary);
}

/* Hero Section */
.hero-section {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: left;
  padding: var(--spacing-5xl, 100px) var(--spacing-3xl, 50px);
  background-color: var(--color-dark);
  position: relative;
  overflow: hidden;
  min-height: 600px;
}

.lab-tag {
  position: absolute;
  top: var(--spacing-3xl, 50px);
  left: var(--spacing-3xl, 50px);
  background-color: var(--color-primary-light);
  color: var(--color-text-light);
  padding: var(--spacing-xs) var(--spacing-md);
  border-radius: var(--border-radius-full, 20px);
  font-size: var(--font-size-xs);
  text-transform: uppercase;
  letter-spacing: 1px;
  z-index: 2;
}

.hero-content {
  max-width: 50%;
  z-index: 1;
}

.heart-icon img {
  width: var(--spacing-3xl, 40px);
  height: var(--spacing-3xl, 40px);
  margin-bottom: var(--spacing-md);
}

.subtitle {
  font-size: var(--font-size-base);
  color: var(--color-text-light);
  margin-bottom: var(--spacing-sm);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.main-title {
  font-size: var(--font-size-6xl, 60px);
  font-weight: var(--font-weight-bold);
  margin-bottom: var(--spacing-xl);
  color: var(--color-text-light);
}

    .main-title .highlight {
      color: var(--color-warning);
      text-decoration: underline;
    }

    .description {
  font-size: var(--font-size-xl);
  color: var(--color-text-light);
  margin-bottom: var(--spacing-2xl, 30px);
  max-width: 600px;
  margin-left: 0;
  margin-right: 0;
}

.hero-buttons {
  display: flex;
  gap: var(--spacing-xl, 20px);
  margin-top: var(--spacing-2xl, 30px);
}

.hero-buttons .el-button {
  margin: 0;
}

.start-button {
  background-color: var(--color-warning);
  border-color: var(--color-warning);
  color: var(--color-text-light);
  padding: var(--spacing-md) var(--spacing-2xl);
  font-size: var(--font-size-lg);
  border-radius: var(--border-radius-lg);
}

.start-button:hover {
  background-color: var(--color-warning-dark);
  border-color: var(--color-warning-dark);
}

.demo-button {
  background-color: var(--color-dark-secondary);
  border-color: var(--color-dark-secondary);
  color: var(--color-text-light);
  padding: var(--spacing-md) var(--spacing-2xl);
  font-size: var(--font-size-lg);
  border-radius: var(--border-radius-lg);
}

.demo-button:hover {
  background-color: var(--color-dark);
  border-color: var(--color-dark);
}

.lab-dashboard-image {
  position: absolute;
  right: var(--spacing-3xl, 50px);
  top: 50%;
  transform: translateY(-50%);
  width: 600px;
  height: auto;
  max-height: 80%;
  object-fit: contain;
  z-index: 1;
}

.hero-metrics {
  display: flex;
  position: absolute;
  bottom: var(--spacing-3xl, 50px);
  left: var(--spacing-3xl, 50px);
  gap: var(--spacing-4xl, 40px);
  z-index: 2;
}

.metric-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  color: var(--color-text-light);
}

.metric-value {
  font-size: var(--font-size-4xl, 36px);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-light);
}

.metric-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Sections common styles */
.pillars-section,
.capabilities-section {
  padding: var(--spacing-5xl, 60px) var(--spacing-xl);
  text-align: center;
  background-color: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border-secondary);
}

.section-subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-text-tertiary);
  margin-bottom: var(--spacing-sm);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.section-title {
  font-size: var(--font-size-4xl, 36px);
  font-weight: var(--font-weight-bold);
  margin-bottom: var(--spacing-xl);
  color: var(--color-dark);
}

.section-description {
  font-size: var(--font-size-lg);
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-4xl, 40px);
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

/* Grid for cards */
.pillars-grid,
.capabilities-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-2xl, 30px);
  max-width: 1200px;
  margin: 0 auto;
}

.pillar-card,
.capability-card {
  padding: var(--spacing-2xl, 30px);
  border-radius: var(--border-radius-lg);
  text-align: left;
  box-shadow: var(--shadow-sm);
}

.pillar-card:hover {
  border: 1px solid var(--color-primary) !important;
}

.pillar-icon,
.capability-icon {
  font-size: var(--font-size-4xl, 40px);
  margin-bottom: var(--spacing-md);
  color: var(--color-primary);
}

.pillar-card h3,
.capability-card h3 {
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-sm);
  color: var(--color-dark);
}

.pillar-card p,
.capability-card p {
  font-size: var(--font-size-base);
  color: var(--color-text-secondary);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .main-title {
    font-size: var(--font-size-4xl, 40px);
  }

  .description {
    font-size: var(--font-size-base);
  }

  .hero-buttons {
    flex-direction: column;
  }

  .hero-buttons .el-button {
    margin: var(--spacing-sm) 0;
    width: 80%;
  }

  .section-title {
    font-size: var(--font-size-2xl, 28px);
  }

  .pillars-grid,
  .capabilities-grid {
    grid-template-columns: 1fr;
  }
}
</style> 