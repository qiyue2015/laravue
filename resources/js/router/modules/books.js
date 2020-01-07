import Layout from '@/layout';

const tableRoutes = {
  path: '/articles',
  component: Layout,
  redirect: '/articles/list',
  name: 'Complex Table',
  meta: {
    title: '书库管理',
    icon: 'table',
    permissions: ['view menu table'],
  },
  children: [
    {
      path: 'list',
      component: () => import('@/views/articles/List'),
      name: 'TreeTableDemo',
      meta: { title: '小说例表' },
    },
    {
      path: 'custom-tree-table',
      component: () => import('@/views/table/TreeTable/CustomTreeTable'),
      name: 'CustomTreeTableDemo',
      meta: { title: '作者例表' },
    },
    {
      path: 'complex-table',
      component: () => import('@/views/table/ComplexTable'),
      name: 'ComplexTable',
      meta: { title: 'complexTable' },
    },
  ],
};
export default tableRoutes;
