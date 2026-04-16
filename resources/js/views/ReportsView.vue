<template>
  <AppLayout>
    <div class="d-flex flex-column flex-lg-row justify-content-lg-end align-items-lg-center gap-3 mb-3">
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary" @click="loadReports(1)">
          Обновить
        </button>
        <button class="btn btn-theme" @click="createReport">
          Сформировать отчёт
        </button>
      </div>
    </div>

    <div class="card-soft bg-white p-3 p-md-4 mb-3">
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label">Название отчёта</label>
          <input v-model="form.name" type="text" class="form-control" placeholder="Например: Отчёт за март" />
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label">Дата с</label>
          <input v-model="form.date_from" type="date" class="form-control" />
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label">Дата по</label>
          <input v-model="form.date_to" type="date" class="form-control" />
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label">Подразделение</label>
          <select v-model="form.department_id" class="form-select">
            <option value="">Все</option>
            <option v-for="department in departments" :key="department.id" :value="department.id">
              {{ department.name }}
            </option>
          </select>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label">Статус</label>
          <select v-model="form.status_id" class="form-select">
            <option value="">Все</option>
            <option v-for="status in statuses" :key="status.id" :value="status.id">
              {{ status.name }}
            </option>
          </select>
        </div>
      </div>

      <div v-if="createError" class="alert alert-danger py-2 mt-3 mb-0">
        {{ createError }}
      </div>
    </div>

    <div class="card-soft bg-white p-2 p-md-3">
      <div v-if="loading" class="text-center py-4 text-muted">
        Загрузка отчётов...
      </div>

      <div v-else-if="reports.length === 0" class="text-center py-4 text-muted">
        Отчёты не найдены
      </div>

      <div v-else class="table-responsive">
        <table class="table align-middle mb-0 report-table">
          <thead>
            <tr>
              <th>Название</th>
              <th>Период</th>
              <th>Создал</th>
              <th>Дата создания</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="report in reports"
              :key="report.id"
              class="report-row-click"
              @click="openReport(report)"
            >
              <td class="fw-semibold">{{ report.name }}</td>
              <td>{{ formatPeriod(report) }}</td>
              <td>{{ report.creator?.last_name || '—' }} {{ report.creator?.first_name || '' }}</td>
              <td>{{ formatDate(report.created_at) }}</td>
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

    <div class="modal fade" id="reportDetailsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title">{{ selectedReport?.name || 'Отчёт' }}</h5>
              <div class="text-muted small">
                {{ selectedReport ? formatPeriod(selectedReport) : '' }}
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body pt-3">
            <div v-if="reportError" class="alert alert-danger py-2">
              {{ reportError }}
            </div>

            <template v-if="selectedReport">
              <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                  <button
                    class="nav-link"
                    :class="{ active: reportTab === 'charts' }"
                    type="button"
                    @click="switchReportTab('charts')"
                  >
                    Графики
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    class="nav-link"
                    :class="{ active: reportTab === 'info' }"
                    type="button"
                    @click="switchReportTab('info')"
                  >
                    Показатели
                  </button>
                </li>
              </ul>

              <div v-if="reportTab === 'charts'" class="row g-3">
                <div class="col-12 col-xl-6">
                  <div class="card-soft bg-white p-3 border">
                    <h6 class="mb-3">Распределение по статусам</h6>
                    <canvas ref="statusChartRef" height="220"></canvas>
                  </div>
                </div>

                <div class="col-12 col-xl-6">
                  <div class="card-soft bg-white p-3 border">
                    <h6 class="mb-3">Распределение по приоритетам</h6>
                    <canvas ref="priorityChartRef" height="220"></canvas>
                  </div>
                </div>

                <div class="col-12">
                  <div class="card-soft bg-white p-3 border">
                    <h6 class="mb-3">Кто больше закрывал задачи</h6>
                    <canvas ref="closersChartRef" height="120"></canvas>
                  </div>
                </div>
              </div>
              
              <div v-if="reportTab === 'info'" class="row g-3">
                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">Всего задач</div>
                    <div class="h3 mb-0">{{ selectedReport.data?.total_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">Открыто</div>
                    <div class="h3 mb-0">{{ selectedReport.data?.open_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">В работе</div>
                    <div class="h3 mb-0">{{ selectedReport.data?.in_progress_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">На проверке</div>
                    <div class="h3 mb-0">{{ selectedReport.data?.review_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">Закрыто</div>
                    <div class="h3 mb-0 text-success">{{ selectedReport.data?.done_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">Отменено</div>
                    <div class="h3 mb-0">{{ selectedReport.data?.cancelled_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">Просрочено</div>
                    <div class="h3 mb-0 text-danger">{{ selectedReport.data?.overdue_tasks ?? 0 }}</div>
                  </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <div class="card-soft bg-white p-3 border">
                    <div class="text-muted small mb-1">Среднее время выполнения</div>
                    <div class="h3 mb-0">{{ selectedReport.data?.avg_completion_days ?? 0 }} дн.</div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="card-soft bg-white p-3 border">
                    <h6 class="mb-3">Кто больше закрывал задачи</h6>

                    <div v-if="!(selectedReport.data?.top_closers || []).length" class="text-muted">
                      Нет данных
                    </div>

                    <div
                      v-for="item in (selectedReport.data?.top_closers || [])"
                      :key="item.name"
                      class="report-row"
                    >
                      <div>{{ item.name }}</div>
                      <div class="fw-semibold">{{ item.count }}</div>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="card-soft bg-white p-3 border">
                    <h6 class="mb-3">Какие задачи были закрыты и кем</h6>

                    <div v-if="!(selectedReport.data?.closed_task_details || []).length" class="text-muted">
                      Нет данных
                    </div>

                    <div v-else class="table-responsive">
                      <table class="table align-middle mb-0 report-table">
                        <thead>
                          <tr>
                            <th>ID</th>
                            <th>Задача</th>
                            <th>Закрыл</th>
                            <th>Дата закрытия</th>
                            <th>Выполнялась</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr
                            v-for="item in (selectedReport.data?.closed_task_details || [])"
                            :key="item.task_id"
                          >
                            <td class="fw-semibold">#{{ item.task_id }}</td>
                            <td>{{ item.title }}</td>
                            <td>{{ item.closed_by }}</td>
                            <td>{{ formatDate(item.completed_at) }}</td>
                            <td>{{ item.completion_days ?? '—' }} дн.</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
            <button
              v-if="canDeleteReports"
              type="button"
              class="btn btn-outline-danger"
              :disabled="deleting"
              @click="deleteReport"
            >
              {{ deleting ? 'Удаление...' : 'Удалить отчёт' }}
            </button>

            <button type="button" class="btn btn-outline-secondary ms-auto" data-bs-dismiss="modal">
              Закрыть
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import { Modal } from 'bootstrap';
import { Chart, BarController, BarElement, CategoryScale, DoughnutController, ArcElement, Tooltip, Legend, LinearScale } from 'chart.js';
import AppLayout from '../layouts/AppLayout.vue';

const chartPalette = [
  '#5DB7A7',
  '#6A90F0',
  '#F4B942',
  '#E85D75',
  '#8A6DF1',
  '#4CC9F0',
  '#43AA8B',
  '#F3722C',
  '#577590',
  '#90BE6D',
];

const priorityColors = {
  low: '#6c757d',
  medium: '#0dcaf0',
  high: '#ffc107',
  critical: '#dc3545',
};

Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  DoughnutController,
  ArcElement,
  Tooltip,
  Legend,
  LinearScale
);

const reports = ref([]);
const departments = ref([]);
const statuses = ref([]);
const loading = ref(false);
const createError = ref('');
const deleting = ref(false);
const selectedReport = ref(null);
const reportError = ref('');
const currentUser = ref(null);
const reportTab = ref('charts');

const statusChartRef = ref(null);
const priorityChartRef = ref(null);
const closersChartRef = ref(null);

let reportModalInstance = null;
let statusChart = null;
let priorityChart = null;
let closersChart = null;

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  from: 0,
  to: 0,
});

const form = reactive({
  name: 'Отчёт по задачам',
  date_from: '',
  date_to: '',
  department_id: '',
  status_id: '',
});

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, pagination.current_page - 2);
  const end = Math.min(pagination.last_page, pagination.current_page + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

const canDeleteReports = computed(() => {
  return Array.isArray(currentUser.value?.roles) &&
    currentUser.value.roles.some(role => ['admin', 'analyst'].includes(role.name));
});

const loadCurrentUser = async () => {
  try {
    const response = await window.axios.get('/api/me');
    currentUser.value = response.data;
  } catch (error) {
    currentUser.value = null;
  }
};

const loadDepartments = async () => {
  try {
    const response = await window.axios.get('/api/departments');
    departments.value = response.data || [];
  } catch (error) {
    departments.value = [];
  }
};

const loadStatuses = async () => {
  try {
    const response = await window.axios.get('/api/task-statuses');
    statuses.value = response.data || [];
  } catch (error) {
    statuses.value = [];
  }
};

const loadReports = async (page = 1) => {
  loading.value = true;

  try {
    const response = await window.axios.get('/api/reports', {
      params: { page },
    });

    const payload = response.data;
    reports.value = payload.data || [];
    pagination.current_page = payload.current_page || 1;
    pagination.last_page = payload.last_page || 1;
    pagination.total = payload.total || 0;
    pagination.from = payload.from || 0;
    pagination.to = payload.to || 0;
  } finally {
    loading.value = false;
  }
};

const createReport = async () => {
  createError.value = '';

  try {
    await window.axios.post('/api/reports', {
      name: form.name,
      date_from: form.date_from || null,
      date_to: form.date_to || null,
      department_id: form.department_id || null,
      status_id: form.status_id || null,
    });

    await loadReports(1);
  } catch (error) {
    createError.value =
      error?.response?.data?.message || 'Не удалось сформировать отчёт';
  }
};

const destroyCharts = () => {
  if (statusChart) {
    statusChart.destroy();
    statusChart = null;
  }
  if (priorityChart) {
    priorityChart.destroy();
    priorityChart = null;
  }
  if (closersChart) {
    closersChart.destroy();
    closersChart = null;
  }
};

const renderCharts = async () => {
  if (!selectedReport.value || reportTab.value !== 'charts') return;

  await nextTick();
  destroyCharts();

  const statusData = selectedReport.value.data?.tasks_by_status || {};
  const priorityData = selectedReport.value.data?.tasks_by_priority || {};
  const closersData = selectedReport.value.data?.top_closers || [];

  if (statusChartRef.value) {
    statusChart = new Chart(statusChartRef.value, {
      type: 'doughnut',
      data: {
        labels: Object.keys(statusData),
        datasets: [
          {
            data: Object.values(statusData),
            backgroundColor: chartPalette.slice(0, Object.keys(statusData).length),
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      },
    });
  }

  if (priorityChartRef.value) {
    const priorityLabels = Object.keys(priorityData);

    priorityChart = new Chart(priorityChartRef.value, {
      type: 'doughnut',
      data: {
        labels: priorityLabels,
        datasets: [
          {
            data: Object.values(priorityData),
            backgroundColor: priorityLabels.map(label => priorityColors[label] || '#6A90F0'),
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
        },
      },
    });
  }

  if (closersChartRef.value) {
    closersChart = new Chart(closersChartRef.value, {
      type: 'bar',
      data: {
        labels: closersData.map(item => item.name),
        datasets: [
          {
            label: 'Закрыто задач',
            data: closersData.map(item => item.count),
            backgroundColor: chartPalette.slice(0, closersData.length),
            borderRadius: 8,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false,
          },
        },
      },
    });
  }
};

const switchReportTab = async (tab) => {
  reportTab.value = tab;

  if (tab === 'charts') {
    await renderCharts();
  }
};

const openReport = async (report) => {
  reportError.value = '';
  reportTab.value = 'charts';

  try {
    const response = await window.axios.get(`/api/reports/${report.id}`);
    selectedReport.value = response.data;
    reportModalInstance?.show();
    await renderCharts();
  } catch (error) {
    reportError.value = 'Не удалось открыть отчёт';
  }
};

const deleteReport = async () => {
  if (!selectedReport.value?.id) return;

  const confirmed = window.confirm(`Удалить отчёт "${selectedReport.value.name}"?`);
  if (!confirmed) return;

  deleting.value = true;
  reportError.value = '';

  try {
    await window.axios.delete(`/api/reports/${selectedReport.value.id}`);
    reportModalInstance?.hide();
    selectedReport.value = null;
    destroyCharts();
    await loadReports(1);
  } catch (error) {
    reportError.value =
      error?.response?.data?.message || 'Не удалось удалить отчёт';
  } finally {
    deleting.value = false;
  }
};

const goToPage = async (page) => {
  if (page < 1 || page > pagination.last_page) return;
  await loadReports(page);
};

const formatDate = (value) => {
  if (!value) return '—';
  return new Date(value).toLocaleString('ru-RU');
};

const formatPeriod = (report) => {
  const from = report.date_from ? new Date(report.date_from).toLocaleDateString('ru-RU') : '—';
  const to = report.date_to ? new Date(report.date_to).toLocaleDateString('ru-RU') : '—';
  return `${from} — ${to}`;
};

const priorityLabel = (priority) => {
  const map = {
    low: 'Низкий',
    medium: 'Средний',
    high: 'Высокий',
    critical: 'Критический',
  };

  return map[priority] || priority;
};

onMounted(async () => {
  await loadCurrentUser();
  await loadDepartments();
  await loadStatuses();
  await loadReports(1);

  const reportModalEl = document.getElementById('reportDetailsModal');
  if (reportModalEl) {
    reportModalInstance = new Modal(reportModalEl);

    reportModalEl.addEventListener('hidden.bs.modal', () => {
      selectedReport.value = null;
      reportError.value = '';
      reportTab.value = 'charts';
      destroyCharts();
    });
  }
});
</script>

<style scoped>
.report-table th {
  font-size: 0.9rem;
  color: #5b7b84;
  font-weight: 700;
  white-space: nowrap;
}

.report-row {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid rgba(120, 150, 160, 0.14);
}

.report-row:last-child {
  border-bottom: none;
}

.report-row-click {
  cursor: pointer;
}

.report-row-click td {
  padding-top: 14px;
  padding-bottom: 14px;
  transition: background-color 0.18s ease;
}

.report-row-click:hover td {
  background-color: rgba(70, 125, 135, 0.18) !important;
}

.nav-tabs .nav-link {
  border-radius: 10px 10px 0 0;
  color: #40606a;
  font-weight: 600;
}

.nav-tabs .nav-link.active {
  color: #23414b;
  background: #f8fffd;
}
</style>
