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

let chartJsPromise = null;

async function loadChartJs() {
  if (!chartJsPromise) {
    chartJsPromise = import('chart.js').then((module) => {
      const {
        Chart,
        BarController,
        BarElement,
        CategoryScale,
        DoughnutController,
        ArcElement,
        Tooltip,
        Legend,
        LinearScale,
      } = module;

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

      return { Chart };
    });
  }

  return chartJsPromise;
}

export function destroyReportCharts(instances) {
  for (const key of Object.keys(instances)) {
    if (instances[key]) {
      instances[key].destroy();
      instances[key] = null;
    }
  }
}

export async function renderReportCharts({ report, refs, instances }) {
  if (!report) return;

  const { Chart } = await loadChartJs();
  destroyReportCharts(instances);

  const statusData = report.data?.tasks_by_status || {};
  const priorityData = report.data?.tasks_by_priority || {};
  const closersData = report.data?.top_closers || [];

  if (refs.status?.value) {
    instances.status = new Chart(refs.status.value, {
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

  if (refs.priority?.value) {
    const priorityLabels = Object.keys(priorityData);

    instances.priority = new Chart(refs.priority.value, {
      type: 'doughnut',
      data: {
        labels: priorityLabels,
        datasets: [
          {
            data: Object.values(priorityData),
            backgroundColor: priorityLabels.map((label) => priorityColors[label] || '#6A90F0'),
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

  if (refs.closers?.value) {
    instances.closers = new Chart(refs.closers.value, {
      type: 'bar',
      data: {
        labels: closersData.map((item) => item.name),
        datasets: [
          {
            label: 'Закрыто задач',
            data: closersData.map((item) => item.count),
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
}
