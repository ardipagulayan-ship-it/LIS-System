import { defineStore } from "pinia";
import api from "@/utils/axios";
import router from "@/router"; // Import the router
import axios from "axios"; // Import base axios

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: JSON.parse(localStorage.getItem("user")) || null,
    token: localStorage.getItem("token") || null,
    isAuthenticated: !!localStorage.getItem("token"),
    loginError: null,
    loading: false,
  }),

  getters: {
    getUser: (state) => state.user,
    getToken: (state) => state.token,
    isLoggedIn: (state) => state.isAuthenticated,
    getLoginError: (state) => state.loginError,
    isLoading: (state) => state.loading,
  },

  actions: {
    setAuthData(token, user) {
      this.token = token;
      this.user = user;
      this.isAuthenticated = true;
      this.loginError = null;
      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));
    },

    clearAuthData() {
      this.token = null;
      this.user = null;
      this.isAuthenticated = false;
      this.loginError = null;
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    },

    async login(credentials) {
      this.loading = true;
      this.loginError = null;
      try {
        // CSRF cookie is best-effort for Bearer-token auth: never let it
        // block login. Derive the backend origin from VITE_API_URL
        // (VITE_APP_URL is not defined in .env).
        try {
          const apiUrl = import.meta.env.VITE_API_URL;
          const origin = apiUrl.replace(/\/api\/?$/, "");
          await axios.get(`${origin}/sanctum/csrf-cookie`, {
            withCredentials: true,
          });
        } catch {
          // ignore — Bearer token auth works without the CSRF cookie
        }

        // Attempt login using the API-prefixed instance
        const response = await api.post("/login", credentials);

        if (response.data.token && response.data.user) {
          this.setAuthData(response.data.token, response.data.user);
          // Redirect to dashboard or intended route
          await router.push("/");
        } else {
          // Handle cases where login might succeed but not return expected data
          throw new Error(
            response.data.message ||
              "Login failed: Invalid response from server",
          );
        }
      } catch (error) {
        this.clearAuthData();
        if (error.response && error.response.status === 422) {
          // Laravel validation errors
          this.loginError =
            error.response.data.message || "Invalid credentials.";
        } else if (
          error.response &&
          error.response.data &&
          error.response.data.message
        ) {
          this.loginError = error.response.data.message;
        } else if (error.message) {
          this.loginError = error.message;
        } else {
          this.loginError = "An unexpected error occurred during login.";
        }
        console.error("Login error:", error);
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      this.loading = true;
      try {
        await api.post("/logout");
      } catch (error) {
        console.error(
          "Logout error (may be expected if token already invalid):",
          error,
        );
        // Even if logout API fails (e.g., expired token), clear client-side data
      } finally {
        this.clearAuthData();
        await router.push("/login");
        this.loading = false;
      }
    },

    async fetchUser() {
      if (!this.token) return; // No token, can't fetch user
      this.loading = true;
      try {
        const response = await api.get("/user");
        if (response.data) {
          this.user = response.data;
          localStorage.setItem("user", JSON.stringify(response.data));
        } else {
          throw new Error("Failed to fetch user data.");
        }
      } catch (error) {
        console.error("Error fetching user:", error);
        // If fetching user fails (e.g., token expired), clear auth and redirect
        this.clearAuthData();
        await router.push("/login");
      } finally {
        this.loading = false;
      }
    },

    // Validate token against the backend; redirect to login only if invalid
    async validateAuth() {
      const token = localStorage.getItem("token");
      if (!token) {
        this.clearAuthData();
        return;
      }
      this.token = token;
      this.isAuthenticated = true;
      try {
        const response = await api.get("/user");
        if (response.data) {
          this.user = response.data;
          localStorage.setItem("user", JSON.stringify(response.data));
          this.isAuthenticated = true;
        } else {
          throw new Error("Invalid user data");
        }
      } catch (error) {
        console.error("Auth validation failed:", error);
        // Token invalid/expired -> clear and redirect to login
        this.clearAuthData();
        await router.push("/login");
      }
    },

    // Action to check authentication status on app load
    // Uses validateAuth() which verifies the token against the backend,
    // preventing auto-logout from stale/expired tokens in localStorage.
    async checkAuth() {
      await this.validateAuth();
    },
  },
});
