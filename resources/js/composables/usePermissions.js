import { useAuthStore } from '@/stores/auth';

export function usePermissions() {
    const authStore = useAuthStore();

    return {
        /**
         * Check if user has a specific permission
         * @param {string} permission - The permission to check
         * @returns {boolean}
         */
        can: (permission) => {
            return authStore.hasPermission(permission);
        },

        /**
         * Check if user has any of the given permissions
         * @param {string[]} permissions - Array of permissions to check
         * @returns {boolean}
         */
        canAny: (permissions) => {
            return authStore.hasAnyPermission(permissions);
        },

        /**
         * Check if user has all of the given permissions
         * @param {string[]} permissions - Array of permissions to check
         * @returns {boolean}
         */
        canAll: (permissions) => {
            return authStore.hasAllPermissions(permissions);
        },

        /**
         * Check if user has a specific role
         * @param {string} role - The role to check
         * @returns {boolean}
         */
        hasRole: (role) => {
            return authStore.hasRole(role);
        },

        /**
         * Check if user can access a module
         * @param {string} module - The module name (e.g., 'patient', 'billing')
         * @returns {boolean}
         */
        canAccessModule: (module) => {
            return authStore.canAccessModule(module);
        },

        /**
         * Check if user is super admin
         * @returns {boolean}
         */
        isSuperAdmin: () => {
            return authStore.isSuperAdmin;
        },

        /**
         * Get all user permissions
         * @returns {string[]}
         */
        permissions: () => {
            return authStore.userPermissions;
        },

        /**
         * Get all user roles
         * @returns {string[]}
         */
        roles: () => {
            return authStore.userRoles;
        }
    };
}
