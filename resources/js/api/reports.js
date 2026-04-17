import { api } from './client';

export async function fetchReports(page = 1) {
  const response = await api().get('/api/reports', {
    params: { page },
  });

  return response.data;
}

export async function fetchReport(reportId) {
  const response = await api().get(`/api/reports/${reportId}`);
  return response.data;
}

export async function createReportRequest(payload) {
  const response = await api().post('/api/reports', payload);
  return response.data;
}

export async function deleteReportRequest(reportId) {
  const response = await api().delete(`/api/reports/${reportId}`);
  return response.data;
}

