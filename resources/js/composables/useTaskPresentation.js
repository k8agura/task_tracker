export const priorityLabels = {
  low: 'Низкий',
  medium: 'Средний',
  high: 'Высокий',
  critical: 'Критический',
};

export const priorityClasses = {
  low: 'text-bg-secondary',
  medium: 'text-bg-info',
  high: 'text-bg-warning',
  critical: 'text-bg-danger',
};

export const historyLabels = {
  created: 'Создание задачи',
  updated: 'Обновление задачи',
  deleted: 'Удаление задачи',
  message_created: 'Добавлено сообщение',
  message_deleted: 'Удалено сообщение',
  attachment_uploaded: 'Загружен файл к задаче',
  attachment_uploaded_to_message: 'Загружен файл к сообщению',
  attachment_deleted: 'Удалён файл',
};

export function useTaskPresentation() {
  const fullName = (user) => {
    if (!user) return '';

    return [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ');
  };

  const formatShortDate = (value) => {
    if (!value) return '';

    return new Date(value).toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  };

  const formatDate = (value) => {
    if (!value) return '';

    return new Date(value).toLocaleString('ru-RU');
  };

  const priorityLabel = (priority) => {
    return priorityLabels[priority] || priority;
  };

  const priorityClass = (priority) => {
    return priorityClasses[priority] || 'text-bg-secondary';
  };

  const isTaskClosed = (task) => ['done', 'cancelled'].includes(task?.status?.code);

  const normalizeDate = (value) => {
    const date = new Date(value);
    date.setHours(0, 0, 0, 0);
    return date;
  };

  const isDueToday = (task) => {
    if (!task?.due_date || isTaskClosed(task)) return false;

    const today = normalizeDate(new Date());
    const dueDate = normalizeDate(task.due_date);

    return dueDate.getTime() === today.getTime();
  };

  const isOverdue = (task) => {
    if (!task?.due_date || isTaskClosed(task)) return false;

    const today = normalizeDate(new Date());
    const dueDate = normalizeDate(task.due_date);

    return dueDate < today;
  };

  const historyLabel = (action) => {
    return historyLabels[action] || action;
  };

  return {
    formatDate,
    formatShortDate,
    fullName,
    historyLabel,
    isDueToday,
    isOverdue,
    priorityClass,
    priorityLabel,
  };
}
