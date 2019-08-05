USE [ComAuto]
GO
/****** Object:  StoredProcedure [dbo].[simple_one]    Script Date: 8/5/2019 2:32:10 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

ALTER PROC [dbo].[simple_one]
    AS

    SELECT COUNT([total_for_month]) AS [total_for_month]
         ,[rs_month]
    FROM [ComAuto].[dbo].[_V_simple_by_date]
    GROUP BY [rs_month]
    ORDER BY [rs_month] ASC
GO

