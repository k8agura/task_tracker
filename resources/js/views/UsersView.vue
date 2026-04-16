<template>
  <AppLayout>
    <div class="d-flex flex-column flex-lg-row justify-content-lg-end align-items-lg-center gap-3 mb-3">
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary" @click="resetFilters">
          Сбросить
        </button>
        <button class="btn btn-outline-secondary" @click="loadUsers(1)">
          Обновить
        </button>
        <button class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#createUserModal">
          Добавить пользователя
        </button>
      </div>
    </div>

    <div class="card-soft bg-white p-3 p-md-4 mb-3">
      <div class="row g-3">
        <div class="col-12 col-xl-5">
          <label class="form-label">Быстрый поиск</label>
          <input
            v-model="filters.search"
            class="form-control"
            placeholder="ФИО, email, должность"
            @input="handleQuickSearch"
          />
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <label class="form-label">Подразделение</label>
          <select v-model="filters.department_id" class="form-select">
            <option value="">Все подразделения</option>
            <option
              v-for="department in departments"
              :key="department.id"
              :value="department.id"
            >
              {{ department.name }}
            </option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-xl-2">
          <label class="form-label">Статус</label>
          <select v-model="filters.is_active" class="form-select">
            <option value="">Все</option>
            <option value="true">Активные</option>
            <option value="false">Неактивные</option>
          </select>
        </div>

        <div class="col-12 col-xl-2 d-flex align-items-end">
          <button class="btn btn-theme w-100" @click="applyFilters">
            Применить
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMessage" class="alert alert-danger py-2">
      {{ errorMessage }}
    </div>

    <div class="card-soft bg-white p-2 p-md-3">
      <div v-if="loading" class="text-center py-4 text-muted">
        Загрузка пользователей...
      </div>

      <div v-else-if="users.length === 0" class="text-center py-4 text-muted">
        Пользователи не найдены
      </div>

      <div v-else class="table-responsive">
        <table class="table align-middle mb-0 user-table">
          <thead>
            <tr>
              <th>Пользователь</th>
              <th>Email</th>
              <th>Должность</th>
              <th>Подразделение</th>
              <th>Роли</th>
              <th>Статус</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="user in users"
              :key="user.id"
              class="user-row-click"
              @click="openEditModal(user)"
            >
              <td>
                <div class="fw-semibold">{{ fullName(user) }}</div>
              </td>
              <td>{{ user.email }}</td>
              <td>{{ user.position || '—' }}</td>
              <td>{{ user.department?.name || '—' }}</td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="badge text-bg-info"
                  >
                    {{ role.name }}
                  </span>
                </div>
              </td>
              <td>
                <span
                  class="badge"
                  :class="user.is_active ? 'text-bg-success' : 'text-bg-secondary'"
                >
                  {{ user.is_active ? 'Активен' : 'Неактивен' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pagination.last_page > 1"
        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3 px-2"
      >
        <div class="small text-muted">
          Показано {{ pagination.from }}–{{ pagination.to }} из {{ pagination.total }}
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            Назад
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            class="btn btn-sm"
            :class="page === pagination.current_page ? 'btn-theme' : 'btn-outline-secondary'"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            class="btn btn-sm btn-outline-secondary"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title">Новый пользователь</h5>
              <div class="text-muted small">Создание учётной записи</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body pt-3">
            <div v-if="createError" class="alert alert-danger py-2">
              {{ createError }}
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Фамилия</label>
                <input v-model="createForm.last_name" class="form-control" />
              </div>

              <div class="col-md-4">
                <label class="form-label">Имя</label>
                <input v-model="createForm.first_name" class="form-control" />
              </div>

              <div class="col-md-4">
                <label class="form-label">Отчество</label>
                <input v-model="createForm.middle_name" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input v-model="createForm.email" type="email" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Должность</label>
                <input v-model="createForm.position" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Пароль</label>
                <input v-model="createForm.password" type="password" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Подтверждение пароля</label>
                <input v-model="createForm.password_confirmation" type="password" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Подразделение</label>
                <select v-model="createForm.department_id" class="form-select">
                  <option value="">Не выбрано</option>
                  <option
                    v-for="department in departments"
                    :key="department.id"
                    :value="department.id"
                  >
                    {{ department.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Роль</label>
                <select v-model="createForm.role" class="form-select">
                  <option value="admin">admin</option>
                  <option value="manager">manager</option>
                  <option value="executor">executor</option>
                  <option value="analyst">analyst</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select v-model="createForm.is_active" class="form-select">
                  <option :value="true">Активен</option>
                  <option :value="false">Неактивен</option>
                </select>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary" @click="closeCreateModal">
              Отмена
            </button>
            <button type="button" class="btn btn-theme" :disabled="creating" @click="createUser">
              {{ creating ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title">Пользователь</h5>
              <div class="text-muted small">Просмотр и редактирование</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body pt-3">
            <div v-if="editError" class="alert alert-danger py-2">
              {{ editError }}
            </div>

            <div v-if="editSuccess" class="alert alert-success py-2">
              {{ editSuccess }}
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Фамилия</label>
                <input v-model="editForm.last_name" class="form-control" />
              </div>

              <div class="col-md-4">
                <label class="form-label">Имя</label>
                <input v-model="editForm.first_name" class="form-control" />
              </div>

              <div class="col-md-4">
                <label class="form-label">Отчество</label>
                <input v-model="editForm.middle_name" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input v-model="editForm.email" type="email" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Должность</label>
                <input v-model="editForm.position" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Новый пароль</label>
                <input v-model="editForm.password" type="password" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Подтверждение</label>
                <input v-model="editForm.password_confirmation" type="password" class="form-control" />
              </div>

              <div class="col-md-6">
                <label class="form-label">Подразделение</label>
                <select v-model="editForm.department_id" class="form-select">
                  <option value="">Не выбрано</option>
                  <option
                    v-for="department in departments"
                    :key="department.id"
                    :value="department.id"
                  >
                    {{ department.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Роль</label>
                <select v-model="editForm.role" class="form-select">
                  <option value="admin">admin</option>
                  <option value="manager">manager</option>
                  <option value="executor">executor</option>
                  <option value="analyst">analyst</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select v-model="editForm.is_active" class="form-select">
                  <option :value="true">Активен</option>
                  <option :value="false">Неактивен</option>
                </select>
              </div>
            </div>
          </div>

            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    :disabled="deleting || saving"
                    @click="deleteUser"
                >
                    {{ deleting ? 'Удаление...' : 'Удалить пользователя' }}
                </button>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" @click="closeEditModal">
                    Отмена
                    </button>
                    <button type="button" class="btn btn-theme" :disabled="saving || deleting" @click="updateUser">
                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Modal } from 'bootstrap';
import AppLayout from '../layouts/AppLayout.vue';

const users = ref([]);
const departments = ref([]);
const loading = ref(false);
const creating = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const createError = ref('');
const editError = ref('');
const editSuccess = ref('');
const searchDebounce = ref(null);
const deleting = ref(false);

let createModalInstance = null;
let editModalInstance = null;

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const filters = reactive({
  search: '',
  department_id: '',
  is_active: '',
});

const createForm = reactive({
  last_name: '',
  first_name: '',
  middle_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  position: '',
  department_id: '',
  is_active: true,
  role: 'executor',
});

const editForm = reactive({
  id: null,
  last_name: '',
  first_name: '',
  middle_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  position: '',
  department_id: '',
  is_active: true,
  role: 'executor',
});

const deleteUser = async () => {
  if (!editForm.id) return;

  const confirmed = window.confirm(
    `Удалить пользователя "${editForm.last_name} ${editForm.first_name}"?`
  );

  if (!confirmed) return;

  deleting.value = true;
  editError.value = '';
  editSuccess.value = '';

  try {
    await window.axios.delete(`/api/users/${editForm.id}`);
    await loadUsers(pagination.current_page);
    closeEditModal();
  } catch (error) {
    editError.value =
      error?.response?.data?.message || 'Не удалось удалить пользователя';
  } finally {
    deleting.value = false;
  }
};

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, pagination.current_page - 2);
  const end = Math.min(pagination.last_page, pagination.current_page + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

const fullName = (user) => {
  return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
};

const loadDepartments = async () => {
  try {
    const response = await window.axios.get('/api/departments');
    departments.value = response.data || [];
  } catch (error) {
    departments.value = [];
  }
};

const loadUsers = async (page = 1) => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const params = { page };

    if (filters.search) params.search = filters.search;
    if (filters.department_id) params.department_id = filters.department_id;
    if (filters.is_active !== '') params.is_active = filters.is_active;

    const response = await window.axios.get('/api/users', { params });
    const payload = response.data;

    users.value = payload.data || [];
    pagination.current_page = payload.current_page || 1;
    pagination.last_page = payload.last_page || 1;
    pagination.total = payload.total || 0;
    pagination.from = payload.from || 0;
    pagination.to = payload.to || 0;
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Не удалось загрузить пользователей';
  } finally {
    loading.value = false;
  }
};

const handleQuickSearch = () => {
  clearTimeout(searchDebounce.value);
  searchDebounce.value = setTimeout(() => {
    loadUsers(1);
  }, 350);
};

const applyFilters = async () => {
  await loadUsers(1);
};

const resetFilters = async () => {
  filters.search = '';
  filters.department_id = '';
  filters.is_active = '';
  await loadUsers(1);
};

const goToPage = async (page) => {
  if (page < 1 || page > pagination.last_page) return;
  await loadUsers(page);
};

const resetCreateForm = () => {
  createForm.last_name = '';
  createForm.first_name = '';
  createForm.middle_name = '';
  createForm.email = '';
  createForm.password = '';
  createForm.password_confirmation = '';
  createForm.position = '';
  createForm.department_id = '';
  createForm.is_active = true;
  createForm.role = 'executor';
  createError.value = '';
};

const createUser = async () => {
  creating.value = true;
  createError.value = '';

  try {
    await window.axios.post('/api/users', {
      last_name: createForm.last_name,
      first_name: createForm.first_name,
      middle_name: createForm.middle_name,
      email: createForm.email,
      password: createForm.password,
      password_confirmation: createForm.password_confirmation,
      position: createForm.position,
      department_id: createForm.department_id || null,
      is_active: createForm.is_active,
      roles: [createForm.role],
    });

    await loadUsers(1);
    closeCreateModal();
  } catch (error) {
    createError.value =
      error?.response?.data?.message || 'Не удалось создать пользователя';
  } finally {
    creating.value = false;
  }
};

const openEditModal = async (user) => {
  editError.value = '';
  editSuccess.value = '';

  try {
    const response = await window.axios.get(`/api/users/${user.id}`);
    const data = response.data;

    editForm.id = data.id;
    editForm.last_name = data.last_name || '';
    editForm.first_name = data.first_name || '';
    editForm.middle_name = data.middle_name || '';
    editForm.email = data.email || '';
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.position = data.position || '';
    editForm.department_id = data.department_id || '';
    editForm.is_active = !!data.is_active;
    editForm.role = data.roles?.[0]?.name || 'executor';

    editModalInstance?.show();
  } catch (error) {
    errorMessage.value = 'Не удалось открыть пользователя';
  }
};

const updateUser = async () => {
  saving.value = true;
  editError.value = '';
  editSuccess.value = '';

  try {
    await window.axios.put(`/api/users/${editForm.id}`, {
      last_name: editForm.last_name,
      first_name: editForm.first_name,
      middle_name: editForm.middle_name,
      email: editForm.email,
      password: editForm.password || null,
      password_confirmation: editForm.password_confirmation || null,
      position: editForm.position,
      department_id: editForm.department_id || null,
      is_active: editForm.is_active,
      roles: [editForm.role],
    });

    await loadUsers(pagination.current_page);
    closeEditModal();
  } catch (error) {
    editError.value =
      error?.response?.data?.message || 'Не удалось сохранить пользователя';
  } finally {
    saving.value = false;
  }
};

const closeCreateModal = () => {
  createModalInstance?.hide();
  resetCreateForm();
};

const closeEditModal = () => {
  editModalInstance?.hide();
  editError.value = '';
  editSuccess.value = '';

  editForm.id = null;
  editForm.last_name = '';
  editForm.first_name = '';
  editForm.middle_name = '';
  editForm.email = '';
  editForm.password = '';
  editForm.password_confirmation = '';
  editForm.position = '';
  editForm.department_id = '';
  editForm.is_active = true;
  editForm.role = 'executor';
};

onMounted(async () => {
  await loadDepartments();
  await loadUsers(1);

  const createModalEl = document.getElementById('createUserModal');
  const editModalEl = document.getElementById('editUserModal');

  if (createModalEl) {
    createModalInstance = new Modal(createModalEl);
    createModalEl.addEventListener('hidden.bs.modal', resetCreateForm);
  }

  if (editModalEl) {
    editModalInstance = new Modal(editModalEl);
    editModalEl.addEventListener('hidden.bs.modal', () => {
      editError.value = '';
      editSuccess.value = '';
    });
  }
});
</script>

<style scoped>
.user-table th {
  font-size: 0.9rem;
  color: #5b7b84;
  font-weight: 700;
  white-space: nowrap;
}

.user-row-click {
  cursor: pointer;
}

.user-row-click td {
  padding-top: 14px;
  padding-bottom: 14px;
  transition: background-color 0.18s ease;
}

.user-row-click:hover td {
  background-color: rgba(70, 125, 135, 0.18) !important;
}
</style>
