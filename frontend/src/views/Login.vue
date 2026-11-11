<template>
  <div class="login-container">
    <div class="two-column-wrapper">
      <!-- Left Column: Sign In Form -->
      <div class="sign-in-column">
        <h2 class="sign-in-title">Sign In</h2>
        <!-- Social Login Icons Area (to be styled later) -->
        <div class="social-icons-container">
          <div class="social-icon">G+</div>
          <div class="social-icon">f</div>
          <div class="social-icon">O</div>
          <div class="social-icon">in</div>
        </div>
        <p class="email-password-text">or use your email password</p>
        <el-form
          ref="loginFormRef"
          :model="loginForm"
          :rules="loginRules"
          label-position="top"
          @submit.prevent="handleLogin"
        >
          <el-form-item prop="email">
            <el-input
              v-model="loginForm.email"
              placeholder="Enter your email"
              :prefix-icon="User"
              clearable
            />
          </el-form-item>
          <el-form-item prop="password">
            <el-input
              v-model="loginForm.password"
              type="password"
              placeholder="Enter your password"
              :prefix-icon="Lock"
              show-password
              clearable
            />
          </el-form-item>
          
          <el-alert 
            v-if="authStore.getLoginError"
            :title="authStore.getLoginError"
            type="error"
            show-icon
            :closable="false"
            style="margin-bottom: 20px;"
          />

          <el-form-item>
            <el-button
              type="primary"
              native-type="submit"
              class="login-button"
              :loading="authStore.isLoading"
            >
              Sign In
            </el-button>
          </el-form-item>
        </el-form>
        <!-- Forgot Password Link (to be styled later) -->
        <div class="forgot-password-container">
          
        </div>
      </div>
      <!-- Right Column: Hello Friend Section -->
      <div class="hello-friend-column">
        <h2>Hello, Friend!</h2>
        <p>Register with your personal details to use all of site features</p>
        <el-button type="primary" class="signup-button">Sign Up</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { User, Lock } from '@element-plus/icons-vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const loginFormRef = ref(null);

const loginForm = ref({
  email: '',
  password: '',
});

const loginRules = ref({
  email: [
    { required: true, message: 'Please enter your email', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email address', trigger: ['blur', 'change'] },
  ],
  password: [
    { required: true, message: 'Please enter your password', trigger: 'blur' },
  ],
});

const handleLogin = async () => {
  if (!loginFormRef.value) return;
  await loginFormRef.value.validate(async (valid) => {
    if (valid) {
      await authStore.login(loginForm.value);
      // Error handling is done within the auth store action
    } else {
      ElMessage.error('Please fill in all required fields correctly.');
      return false;
    }
  });
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(to right, var(--color-bg-primary), var(--color-bg-secondary)); /* Purple gradient */
}

.two-column-wrapper {
    display: flex;
    background-color: var(--color-bg-primary);
    border-radius: var(--border-radius-xl);
    box-shadow: var(--shadow-xl);
    overflow: hidden;
    width: 900px; /* Adjust as needed */
    position: relative; /* Added for pseudo-element positioning */
  }

.sign-in-column {
    flex: 1;
    padding: var(--spacing-3xl);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative; /* Added for pseudo-element positioning */
  }

  .hello-friend-column::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to right, var(--color-primary-dark), var(--color-primary));
    border-radius: var(--border-radius-full, 100px) 0 0 var(--border-radius-full, 100px); /* Curved shape on the right side */
    z-index: -1; /* Ensure it's behind the hello-friend-column */
  }

.sign-in-title {
  font-size: var(--font-size-3xl, 32px);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  margin-bottom: var(--spacing-xl);
}

.social-icons-container {
    display: flex;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-xl);
  }

  .social-icon {
    width: var(--spacing-3xl, 40px);
    height: var(--spacing-3xl, 40px);
    border: 1px solid var(--color-border-primary);
    border-radius: var(--border-radius-full);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: var(--font-size-xl);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-normal) ease;
  }

  .social-icon:hover {
    background-color: var(--color-bg-tertiary);
    color: var(--color-dark);
    border-color: var(--color-dark);
  }

.email-password-text {
  color: var(--color-text-tertiary);
  margin-bottom: var(--spacing-xl);
}

.hello-friend-column {
    flex: 1;
    background: var(--color-dark); /* Purple gradient */
    color: var(--color-text-light);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: var(--spacing-3xl);
    position: relative;
    border-top-left-radius: var(--border-radius-full, 200px); /* Curved shape */
    border-bottom-left-radius: var(--border-radius-full, 100px); /* Curved shape */
    z-index: 2; /* Ensure it's above the pseudo-element */
  }

.login-button {
  width: 100%;
  background-color: var(--color-primary);
  border-color: var(--color-primary);
}

.forgot-password-container {
    width: 100%;
    text-align: right;
    margin-top: var(--spacing-sm);
  }

  .forgot-password-link {
    color: var(--color-primary);
    text-decoration: none;
    font-size: var(--font-size-base);
  }

  .login-button:hover {
    background-color: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
  }

  .forgot-password-container {
    width: 100%;
    text-align: right;
    margin-top: var(--spacing-sm);
  }

  .forgot-password-link {
    color: var(--color-primary);
    text-decoration: none;
    font-size: var(--font-size-base);
  }

  .signup-button {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-text-light);
    margin-top: var(--spacing-xl);
  }

  .signup-button:hover {
    background-color: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
    color: var(--color-text-light);
  }
</style> 