import { useAuthStore } from '@/stores/auth';

/**
 * v-can directive
 * Usage: v-can="'permission.name'" or v-can="['permission1', 'permission2']"
 * Hides element if user doesn't have the specified permission(s)
 * Multiple permissions are treated as OR (user needs at least one)
 */
export const canDirective = {
    mounted(el, binding) {
        const authStore = useAuthStore();
        const permission = binding.value;

        let hasPermission = false;

        if (Array.isArray(permission)) {
            // Multiple permissions - check if user has any
            hasPermission = authStore.hasAnyPermission(permission);
        } else if (typeof permission === 'string') {
            // Single permission
            hasPermission = authStore.hasPermission(permission);
        }

        if (!hasPermission) {
            // Hide the element
            el.style.display = 'none';
        }
    },

    updated(el, binding) {
        const authStore = useAuthStore();
        const permission = binding.value;

        let hasPermission = false;

        if (Array.isArray(permission)) {
            hasPermission = authStore.hasAnyPermission(permission);
        } else if (typeof permission === 'string') {
            hasPermission = authStore.hasPermission(permission);
        }

        // Show or hide based on permission
        el.style.display = hasPermission ? '' : 'none';
    }
};

/**
 * v-can-all directive
 * Usage: v-can-all="['permission1', 'permission2']"
 * Hides element if user doesn't have ALL specified permissions
 */
export const canAllDirective = {
    mounted(el, binding) {
        const authStore = useAuthStore();
        const permissions = binding.value;

        if (!Array.isArray(permissions)) {
            console.warn('v-can-all expects an array of permissions');
            return;
        }

        const hasAllPermissions = authStore.hasAllPermissions(permissions);

        if (!hasAllPermissions) {
            el.style.display = 'none';
        }
    },

    updated(el, binding) {
        const authStore = useAuthStore();
        const permissions = binding.value;

        if (!Array.isArray(permissions)) {
            return;
        }

        const hasAllPermissions = authStore.hasAllPermissions(permissions);
        el.style.display = hasAllPermissions ? '' : 'none';
    }
};

/**
 * v-role directive
 * Usage: v-role="'role-name'" or v-role="['role1', 'role2']"
 * Hides element if user doesn't have the specified role(s)
 */
export const roleDirective = {
    mounted(el, binding) {
        const authStore = useAuthStore();
        const role = binding.value;

        let hasRole = false;

        if (Array.isArray(role)) {
            // Multiple roles - check if user has any
            hasRole = role.some(r => authStore.hasRole(r));
        } else if (typeof role === 'string') {
            // Single role
            hasRole = authStore.hasRole(role);
        }

        if (!hasRole) {
            el.style.display = 'none';
        }
    },

    updated(el, binding) {
        const authStore = useAuthStore();
        const role = binding.value;

        let hasRole = false;

        if (Array.isArray(role)) {
            hasRole = role.some(r => authStore.hasRole(r));
        } else if (typeof role === 'string') {
            hasRole = authStore.hasRole(role);
        }

        el.style.display = hasRole ? '' : 'none';
    }
};
